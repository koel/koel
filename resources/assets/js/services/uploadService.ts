import { reactive } from 'vue'
import { http } from '@/services/http'
import { postWithProgress } from '@/services/http'
import { postJson, putToStorageWithProgress } from '@/services/httpUpload'
import type { UploadResponse } from '@/services/httpUpload'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { eventBus } from '@/utils/eventBus'
import { logger } from '@/utils/logger'

const HTTP_ACCEPTED = 202

const MAX_PARALLEL_UPLOADS = 5
const MIN_PARALLEL_UPLOADS = 2
const MAX_ATTEMPTS = 3
const RETRY_DELAY_MS = 2000
const TRANSIENT_FAILURE_STATUSES = [502, 503, 504]
const UNFINISHED_STATUSES: UploadStatus[] = ['Ready', 'Uploading', 'Retrying']

interface PresignedUpload {
  key: string
  url: string
  headers: Record<string, string>
  expires_at: string
}

export interface UploadResult {
  song: Song
  album: Album
  upload_key?: string | null
}

export interface UploadFailure {
  upload_key: string
  message: string
}

export type UploadStatus =
  | 'Ready'
  | 'Uploading'
  | 'Retrying'
  | 'Processing'
  | 'Uploaded'
  | 'Canceled'
  | 'Errored'
  | 'Skipped'

export interface UploadFile {
  id: string
  uploadKey?: string
  file: File
  status: UploadStatus
  name: string
  progress: number
  message?: string
  attempts?: number
  song?: Song
}

export interface DuplicateUpload {
  type: 'duplicate-uploads'
  id: string
  song_title: string | null
  artist_name: string | null
  filename: string
  created_at: string
}

export const uploadService = {
  state: reactive({
    files: [] as UploadFile[],
    duplicatedSongs: [] as DuplicateUpload[],
  }),

  leavingIsGuarded: false,

  abortHandles: new Map<string, () => void>(),

  parallelUploadLimit: MAX_PARALLEL_UPLOADS,

  queue(file: UploadFile | UploadFile[]) {
    this.ensureLeavingIsGuarded()
    this.state.files = this.state.files.concat(file)
    this.proceed()
  },

  getUnfinishedFiles() {
    return this.state.files.filter(({ status }) => UNFINISHED_STATUSES.includes(status))
  },

  ensureLeavingIsGuarded() {
    if (this.leavingIsGuarded) {
      return
    }

    this.leavingIsGuarded = true

    window.addEventListener('beforeunload', event => {
      if (this.getUnfinishedFiles().length) {
        event.preventDefault()
      }
    })
  },

  remove(file: UploadFile) {
    this.abortHandles.delete(file.id)
    this.state.files = this.state.files.filter(f => f !== file)
    this.proceed()
  },

  abort(file: UploadFile) {
    this.abortHandles.get(file.id)?.()
    this.abortHandles.delete(file.id)
  },

  proceed() {
    const remainingSlots = this.parallelUploadLimit - this.getUploadingFiles().length

    if (remainingSlots <= 0) {
      return
    }

    for (let i = 0; i < remainingSlots; ++i) {
      const file = this.getUploadCandidate()
      file && this.upload(file)
    }
  },

  getUploadingFiles() {
    return this.state.files.filter(({ status }) => status === 'Uploading')
  },

  getUploadCandidate() {
    return this.state.files.find(({ status }) => status === 'Ready')
  },

  async upload(file: UploadFile) {
    if (file.status === 'Uploading') {
      return
    }

    file.progress = 0
    file.status = 'Uploading'
    file.attempts = (file.attempts ?? 0) + 1

    const trackProgress = (e: ProgressEvent) => (file.progress = (e.loaded * 100) / e.total)

    let response: UploadResponse<UploadResult | null>

    try {
      response = commonStore.state.supports_presigned_uploads
        ? await this.uploadViaPresignedUrl(file, trackProgress)
        : await this.uploadDirectlyToServer(file, trackProgress)
    } catch (error: unknown) {
      if (error instanceof DOMException && error.name === 'AbortError') {
        file.status = 'Canceled'
        file.message = 'Canceled'
        this.proceed()
        return
      }

      logger.error(error)

      const err = error as {
        status?: number
        data?: unknown
      }

      if (this.isTransientFailure(err) && file.attempts < MAX_ATTEMPTS) {
        this.slowDown()
        this.retryLater(file)
        this.proceed()
        return
      }

      file.status = 'Errored'

      const responseData = err.data
      const isObjectResponse = responseData !== null && typeof responseData === 'object'

      if (err.status === 409 && isObjectResponse) {
        this.state.duplicatedSongs.push(responseData as DuplicateUpload)
        this.remove(file)
        return
      }

      const message =
        isObjectResponse && 'message' in responseData ? (responseData as { message?: unknown }).message : undefined

      file.message = typeof message === 'string' && message ? message : 'Server error'

      this.proceed() // upload the next file

      return
    } finally {
      this.abortHandles.delete(file.id)
    }

    if (response.status === HTTP_ACCEPTED && file.uploadKey) {
      if (file.status === 'Uploading') {
        file.status = 'Processing'
      }
    } else {
      file.status = 'Uploaded'
      response.data && this.handleUploadResult(response.data, file)
    }

    this.speedUp()
    this.proceed()
  },

  async uploadDirectlyToServer(file: UploadFile, onProgress: (e: ProgressEvent) => void) {
    const formData = new FormData()
    formData.append('file', file.file)

    const { promise, abort } = postWithProgress<UploadResult | null>('upload', formData, onProgress)
    this.abortHandles.set(file.id, abort)

    return await promise
  },

  async uploadViaPresignedUrl(file: UploadFile, onProgress: (e: ProgressEvent) => void) {
    const presigning = postJson<PresignedUpload>('upload/presign', {
      file_name: file.file.name,
      file_size: file.file.size,
    })
    this.abortHandles.set(file.id, presigning.abort)
    const { data: presigned } = await presigning.promise
    file.uploadKey = presigned.key

    const sending = putToStorageWithProgress(presigned.url, file.file, presigned.headers, onProgress)
    this.abortHandles.set(file.id, sending.abort)
    await sending.promise

    const completing = postJson<UploadResult | null>('upload/complete', { key: presigned.key })
    this.abortHandles.set(file.id, completing.abort)

    return await completing.promise
  },

  async fetchDuplicates() {
    this.state.duplicatedSongs = await http.get<DuplicateUpload[]>('duplicate-uploads')
  },

  async keepDuplicate(id: DuplicateUpload['id']) {
    const result = await http.post<UploadResult>(`duplicate-uploads/${id}`)
    this.state.duplicatedSongs = this.state.duplicatedSongs.filter(s => s.id !== id)
    this.handleUploadResult(result)
  },

  async keepAllDuplicates() {
    const results = await http.post<UploadResult[]>('duplicate-uploads')
    this.state.duplicatedSongs = []
    results.forEach(result => this.handleUploadResult(result))
  },

  async discardDuplicate(id: DuplicateUpload['id']) {
    await http.delete(`duplicate-uploads/${id}`)
    this.state.duplicatedSongs = this.state.duplicatedSongs.filter(s => s.id !== id)
  },

  async discardAllDuplicates() {
    await http.delete('duplicate-uploads')
    this.state.duplicatedSongs = []
  },

  isTransientFailure: (failure: { status?: number }) =>
    failure.status === undefined || TRANSIENT_FAILURE_STATUSES.includes(failure.status),

  retryLater(file: UploadFile) {
    file.status = 'Retrying'
    file.progress = 0

    window.setTimeout(
      () => {
        if (file.status === 'Retrying') {
          file.status = 'Ready'
          this.proceed()
        }
      },
      RETRY_DELAY_MS * (file.attempts ?? 1),
    )
  },

  slowDown() {
    this.parallelUploadLimit = MIN_PARALLEL_UPLOADS
  },

  speedUp() {
    this.parallelUploadLimit = Math.min(MAX_PARALLEL_UPLOADS, this.parallelUploadLimit + 1)
  },

  handleUploadResult(result: UploadResult, uploadedFile?: UploadFile) {
    playableStore.syncWithVault(result.song)
    playableStore.invalidateAlbumAndArtistSongCaches(result.song)
    albumStore.syncWithVault(result.album)
    commonStore.state.song_length += 1
    eventBus.emit('SONG_UPLOADED', result.song)

    const file = uploadedFile ?? this.findByUploadKey(result.upload_key)

    if (file) {
      file.status = 'Uploaded'
      file.song = result.song
    }
  },

  handleUploadFailure(failure: UploadFailure) {
    const file = this.findByUploadKey(failure.upload_key)

    if (file) {
      file.status = 'Errored'
      file.message = failure.message
      this.proceed()
    }
  },

  findByUploadKey(key?: string | null) {
    return key ? this.state.files.find(file => file.uploadKey === key) : undefined
  },

  retry(file: UploadFile) {
    // simply reset the status and wait for the next process
    this.resetFile(file)
    this.proceed()
  },

  retryAll() {
    this.state.files.filter(({ status }) => status === 'Errored' || status === 'Canceled').forEach(this.resetFile)

    this.proceed()
  },

  resetFile: (file: UploadFile) => {
    file.status = 'Ready'
    file.progress = 0
    file.uploadKey = undefined
    file.message = undefined
    file.attempts = 0
  },

  removeFailed() {
    this.state.files = this.state.files.filter(({ status }) => status !== 'Errored' && status !== 'Canceled')
  },
}
