import { without } from 'lodash'
import { reactive } from 'vue'
import { UploadFile, UploadStatus } from '@/config'
import { http } from '@/services'
import { albumStore, artistStore, songStore } from '@/stores'
import { eventBus } from '@/utils'

export const upload = {
  state: reactive({
    files: [] as UploadFile[]
  }),

  simultaneousUploads: 5,

  queue (file: UploadFile | UploadFile[]) {
    this.state.files = this.state.files.concat(file)
    this.proceed()
  },

  remove (file: UploadFile) {
    this.state.files = without(this.state.files, file)
    this.proceed()
  },

  proceed () {
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

  getUploadingFiles () {
    return this.state.files.filter(file => file.status === 'Uploading')
  },

  getUploadCandidate () {
    return this.state.files.find(file => file.status === 'Ready')
  },

  async upload (file: UploadFile) {
    if (file.status === 'Uploading') {
      return
    }

    const formData = new FormData()
    formData.append('file', file.file)
    file.progress = 0
    file.status = 'Uploading'

    try {
      const song = await http.post<SongUploadResult>('upload', formData, (progressEvent: ProgressEvent) => {
        file.progress = progressEvent.loaded * 100 / progressEvent.total
      })

      file.status = 'Uploaded'
      this.populateUploadResultIntoStores(song)
      this.proceed() // upload the next file
      window.setTimeout(() => this.remove(file), 1000)
      eventBus.emit('SONG_UPLOADED')
    } catch (error) {
      // @ts-ignore
      file.message = `Upload failed: ${error.response?.data?.message || 'Unknown error'}`
      file.status = 'Errored'
      this.proceed() // upload the next file
    } finally {
      this.checkUploadQueueStatus()
    }
  },

  retry (file: UploadFile) {
    // simply reset the status and wait for the next process
    this.resetFile(file)
    this.proceed()
  },

  retryAll () {
    this.state.files.forEach(this.resetFile)
    this.proceed()
  },

  resetFile: (file: UploadFile) => {
    file.status = 'Ready'
    file.progress = 0
  },

  clear () {
    this.state.files = []
  },

  removeFailed () {
    this.state.files = this.state.files.filter(file => file.status !== 'Errored')
  },

  checkUploadQueueStatus () {
    const uploadingFiles = this.state.files.filter(file => file.status === 'Uploading')

    if (uploadingFiles.length === 0) {
      eventBus.emit('UPLOAD_QUEUE_FINISHED')
    }
  },

  getFilesByStatus (status: UploadStatus) {
    return this.state.files.filter(file => file.status === status)
  },

  populateUploadResultIntoStores (uploadResult: SongUploadResult) {
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
