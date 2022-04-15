import { without } from 'lodash'
import { UploadFile, UploadStatus } from '@/config'
import { http } from '@/services'
import { songStore, albumStore, artistStore } from '@/stores'
import { eventBus } from '@/utils'

export const upload = {
  state: {
    files: [] as UploadFile[]
  },

  simultaneousUploads: 5,

  queue (file: UploadFile | UploadFile[]): void {
    this.state.files = this.state.files.concat(file)
    this.proceed()
  },

  remove (file: UploadFile): void {
    this.state.files = without(this.state.files, file)
    this.proceed()
  },

  proceed (): void {
    const remainingSlots = this.simultaneousUploads - this.getUploadingFiles().length

    if (remainingSlots <= 0) {
      return
    }

    for (let i = 0; i < remainingSlots; ++i) {
      const file = this.getUploadCandidate()

      if (file) {
        this.upload(file)
      }
    }
  },

  getUploadingFiles (): UploadFile[] {
    return this.state.files.filter(file => file.status === 'Uploading')
  },

  getUploadCandidate (): UploadFile | undefined {
    return this.state.files.find(file => file.status === 'Ready')
  },

  async upload (file: UploadFile): Promise<void> {
    if (file.status === 'Uploading') {
      return
    }

    const formData = new FormData()
    formData.append('file', file.file)
    file.progress = 0
    file.status = 'Uploading'

    try {
      const song = await http.post<SongUploadResult>('upload', formData, (progressEvent: ProgressEvent): void => {
        file.progress = progressEvent.loaded * 100 / progressEvent.total
      })

      file.status = 'Uploaded'
      this.populateUploadResultIntoStores(song)
      this.proceed() // upload the next file
      window.setTimeout((): void => this.remove(file), 1000)
      eventBus.emit('SONG_UPLOADED')
    } catch (error) {
      // @ts-ignore
      file.message = `Upload failed: ${ error.response?.data?.message || 'Unknown error' }`
      file.status = 'Errored'
      this.proceed() // upload the next file
    } finally {
      this.checkUploadQueueStatus()
    }
  },

  retry (file: UploadFile): void {
    // simply reset the status and wait for the next process
    this.resetFile(file)
    this.proceed()
  },

  retryAll (): void {
    this.state.files.forEach(this.resetFile)
    this.proceed()
  },

  resetFile: (file: UploadFile): void => {
    file.status = 'Ready'
    file.progress = 0
  },

  clear (): void {
    this.state.files = []
  },

  removeFailed (): void {
    this.state.files = this.state.files.filter(file => file.status !== 'Errored')
  },

  checkUploadQueueStatus (): void {
    const uploadingFiles = this.state.files.filter(file => file.status === 'Uploading')

    if (uploadingFiles.length === 0) {
      eventBus.emit('UPLOAD_QUEUE_FINISHED')
    }
  },

  getFilesByStatus (status: UploadStatus): UploadFile[] {
    return this.state.files.filter(file => file.status === status)
  },

  populateUploadResultIntoStores (uploadResult: SongUploadResult): void {
    let artist = artistStore.byId(uploadResult.artist.id)!
    let album = albumStore.byId(uploadResult.album.id)!

    if (!artist) {
      artist = Object.assign(uploadResult.artist, {
        playCount: 0,
        length: 0,
        fmtLength: '',
        info: null,
        albums: [],
        songs: []
      })

      artistStore.add(artist)
    }

    if (!album) {
      album = Object.assign(uploadResult.album, {
        artist,
        songs: [],
        playCount: 0,
        length: 0,
        fmtLength: '',
        info: null
      })

      albumStore.add(album)
    }

    const song: Song = {
      album,
      artist,
      id: uploadResult.id,
      album_id: uploadResult.album.id,
      artist_id: uploadResult.artist.id,
      title: uploadResult.title,
      length: uploadResult.length,
      track: uploadResult.track,
      disc: uploadResult.disc,
      lyrics: '',
      playCount: 0,
      liked: false
    }

    songStore.setupSong(song)
    songStore.all.push(song)
  }
}
