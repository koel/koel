import { without } from 'lodash'
import { reactive } from 'vue'
import { postWithProgress } from '@/services/http'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { eventBus } from '@/utils/eventBus'
import { logger } from '@/utils/logger'

export interface UploadResult {
  song: Song
  album: Album
}

export type UploadStatus = 'Ready' | 'Uploading' | 'Uploaded' | 'Canceled' | 'Errored'

export interface UploadFile {
  id: string
  file: File
  status: UploadStatus
  name: string
  progress: number
  message?: string
}

export const uploadService = {
  state: reactive({
    files: [] as UploadFile[],
  }),

  abortHandles: new Map<string, () => void>(),

  simultaneousUploads: 5,

  queue(file: UploadFile | UploadFile[]) {
    this.state.files = this.state.files.concat(file)
    this.proceed()
  },

  remove(file: UploadFile) {
    this.abortHandles.delete(file.id)
    this.state.files = without(this.state.files, file)
    this.proceed()
  },

  abort(file: UploadFile) {
    this.abortHandles.get(file.id)?.()
    this.abortHandles.delete(file.id)
  },

  proceed() {
    const remainingSlots = this.simultaneousUploads - this.getUploadingFiles().length

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

  shouldWarnUponWindowUnload() {
    return this.state.files.length > 0
  },

  async upload(file: UploadFile) {
    if (file.status === 'Uploading') {
      return
    }

    const formData = new FormData()
    formData.append('file', file.file)
    file.progress = 0
    file.status = 'Uploading'

    const { promise, abort } = postWithProgress<UploadResult | null>('upload', formData, (e: ProgressEvent) => {
      file.progress = (e.loaded * 100) / e.total
    })

    this.abortHandles.set(file.id, abort)

    try {
      const result = await promise

      if (result?.song && result?.album) {
        file.status = 'Uploaded'
        this.handleUploadResult(result)
        window.setTimeout(() => this.remove(file), 1000)
      } else {
        file.status = 'Errored'
        file.message = 'Upload failed: Server returned an unexpected response.'
      }

      this.proceed()
    } catch (error: unknown) {
      if (error instanceof DOMException && error.name === 'AbortError') {
        file.status = 'Canceled'
        file.message = 'Upload cancelled.'
        this.proceed()
        return
      }

      logger.error(error)
      file.status = 'Errored'

      if (error instanceof Error && 'responseData' in error && (error as any).responseData?.message) {
        file.message = `Upload failed: ${(error as any).responseData.message}`
      } else {
        file.message = 'Upload failed: Unknown error.'
      }

      this.proceed() // upload the next file
    } finally {
      this.abortHandles.delete(file.id)
    }
  },

  handleUploadResult: (result: UploadResult) => {
    playableStore.syncWithVault(result.song)
    albumStore.syncWithVault(result.album)
    commonStore.state.song_length += 1
    eventBus.emit('SONG_UPLOADED', result.song)
  },

  retry(file: UploadFile) {
    // simply reset the status and wait for the next process
    this.resetFile(file)
    this.proceed()
  },

  retryAll() {
    this.state.files.forEach(this.resetFile)
    this.proceed()
  },

  resetFile: (file: UploadFile) => {
    file.status = 'Ready'
    file.progress = 0
  },

  removeFailed() {
    this.state.files = this.state.files.filter(({ status }) => status !== 'Errored')
  },
}
