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
import { UploadTracker } from '@/services/uploadTracker'

const HTTP_ACCEPTED = 202

const MAX_PARALLEL_UPLOADS = 5
const MIN_PARALLEL_UPLOADS = 2
const MAX_ATTEMPTS = 3
const RETRY_DELAY_MS = 2000
const TRANSIENT_FAILURE_STATUSES = [502, 503, 504]
const UNFINISHED_STATUSES: UploadStatus[] = ['Ready', 'Uploading', 'Retrying']
const FAILED_STATUSES: UploadStatus[] = ['Errored', 'Canceled']

const RESET_CHANGES: Partial<UploadFile> = {
  status: 'Ready',
  progress: 0,
  uploadKey: undefined,
  message: undefined,
  attempts: 0,
}

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

export interface UploadDuplication {
  upload_key: string
  duplicate: DuplicateUpload
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
    duplicatedSongs: [] as DuplicateUpload[],
  }),

  tracker: new UploadTracker(),

  leavingIsGuarded: false,

  abortHandles: new Map<string, () => void>(),

  parallelUploadLimit: MAX_PARALLEL_UPLOADS,

  queue(file: UploadFile | UploadFile[]) {
    this.ensureLeavingIsGuarded()
    this.tracker.add(Array<UploadFile>().concat(file))
    this.proceed()
  },

  countUnfinishedFiles() {
    return this.tracker.count(UNFINISHED_STATUSES)
  },

  ensureLeavingIsGuarded() {
    if (this.leavingIsGuarded) {
      return
    }

    this.leavingIsGuarded = true

    window.addEventListener('beforeunload', event => {
      if (this.countUnfinishedFiles()) {
        event.preventDefault()
      }
    })
  },

  remove(file: UploadFile) {
    this.abortHandles.delete(file.id)
    this.tracker.remove(file)
    this.proceed()
  },

  abort(file: UploadFile) {
    this.abortHandles.get(file.id)?.()
    this.abortHandles.delete(file.id)
  },

  proceed() {
    const remainingSlots = this.parallelUploadLimit - this.countUploadingFiles()

    if (remainingSlots <= 0) {
      return
    }

    for (let i = 0; i < remainingSlots; ++i) {
      const file = this.getUploadCandidate()
      file && this.upload(file)
    }
  },

  countUploadingFiles() {
    return this.tracker.count(['Uploading'])
  },

  getUploadCandidate() {
    return this.tracker.getFirstReadyFile()
  },

  async upload(file: UploadFile) {
    const attempts = (file.attempts ?? 0) + 1

    this.tracker.update(file, { progress: 0, status: 'Uploading', attempts })

    const trackProgress = (e: ProgressEvent) => this.tracker.update(file, { progress: (e.loaded * 100) / e.total })

    let response: UploadResponse<UploadResult | null>

    try {
      response = commonStore.state.supports_presigned_uploads
        ? await this.uploadViaPresignedUrl(file, trackProgress)
        : await this.uploadDirectlyToServer(file, trackProgress)
    } catch (error: unknown) {
      if (error instanceof DOMException && error.name === 'AbortError') {
        this.tracker.update(file, { status: 'Canceled', message: 'Canceled' })
        this.proceed()
        return
      }

      logger.error(error)

      const err = error as {
        status?: number
        data?: unknown
      }

      if (this.isTransientFailure(err) && attempts < MAX_ATTEMPTS) {
        this.slowDown()
        this.retryLater(file)
        this.proceed()
        return
      }

      this.tracker.update(file, { status: 'Errored' })

      const responseData = err.data
      const isObjectResponse = responseData !== null && typeof responseData === 'object'

      if (err.status === 409 && isObjectResponse) {
        this.markAsDuplicate(file, responseData as DuplicateUpload)
        return
      }

      const message =
        isObjectResponse && 'message' in responseData ? (responseData as { message?: unknown }).message : undefined

      this.tracker.update(file, { message: typeof message === 'string' && message ? message : 'Server error' })

      this.proceed() // upload the next file

      return
    } finally {
      this.abortHandles.delete(file.id)
    }

    if (response.status === HTTP_ACCEPTED && file.uploadKey) {
      if (file.status === 'Uploading') {
        this.tracker.update(file, { status: 'Processing' })
      }
    } else {
      this.tracker.update(file, { status: 'Uploaded' })
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
    this.tracker.update(file, { uploadKey: presigned.key })

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
    this.tracker.update(file, { status: 'Retrying', progress: 0 })

    window.setTimeout(
      () => {
        if (file.status === 'Retrying') {
          this.tracker.update(file, { status: 'Ready' })
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
      this.tracker.update(file, { status: 'Uploaded', song: result.song })
    }
  },

  handleUploadFailure(failure: UploadFailure) {
    const file = this.findByUploadKey(failure.upload_key)

    if (file) {
      this.tracker.update(file, { status: 'Errored', message: failure.message })
      this.proceed()
    }
  },

  handleUploadDuplication(duplication: UploadDuplication) {
    const file = this.findByUploadKey(duplication.upload_key)

    if (file) {
      this.markAsDuplicate(file, duplication.duplicate)
    }
  },

  markAsDuplicate(file: UploadFile, duplicate: DuplicateUpload) {
    this.state.duplicatedSongs.push(duplicate)
    this.remove(file)
  },

  findByUploadKey(key?: string | null) {
    return key ? this.tracker.findByUploadKey(key) : undefined
  },

  retry(file: UploadFile) {
    // simply reset the status and wait for the next process
    this.resetFile(file)
    this.proceed()
  },

  retryAll() {
    this.tracker.updateMany(this.tracker.getFilesWithStatus(FAILED_STATUSES), RESET_CHANGES)

    this.proceed()
  },

  resetFile(file: UploadFile) {
    this.tracker.update(file, RESET_CHANGES)
  },

  removeFailed() {
    this.tracker.removeWithStatus(FAILED_STATUSES)
  },
}
