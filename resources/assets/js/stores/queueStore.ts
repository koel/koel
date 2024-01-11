import { reactive } from 'vue'
import { differenceBy, unionBy } from 'lodash'
import { arrayify } from '@/utils'
import { http } from '@/services'
import { songStore } from '@/stores'

export const queueStore = {
  state: reactive<{ songs: Song[] }>({
    songs: []
  }),

  init (savedState: QueueState) {
    // don't set this.all here, as it would trigger saving state
    this.state.songs = songStore.syncWithVault(savedState.songs)

    if (!this.state.songs.length) {
      return
    }

    if (savedState.current_song) {
      songStore.syncWithVault(savedState.current_song)[0].playback_state = 'Paused'
    } else {
      this.all[0].playback_state = 'Paused'
    }
  },

  get all () {
    return this.state.songs
  },

  set all (songs: Song[]) {
    this.state.songs = songStore.syncWithVault(songs)
    this.saveState()
  },

  get first () {
    return this.all[0]
  },

  get last () {
    return this.all[this.all.length - 1]
  },

  contains (song: Song) {
    return this.all.includes(reactive(song))
  },

  /**
   * Add song(s) to the end of the current queue.
   */
  queue (songs: Song | Song[]) {
    this.unqueue(songs)
    this.all = unionBy(this.all, arrayify(songs), 'id')
  },

  queueIfNotQueued (song: Song) {
    if (!this.contains(song)) {
      this.queueAfterCurrent(song)
    }
  },

  queueToTop (songs: Song | Song[]) {
    this.all = unionBy(arrayify(songs), this.all, 'id')
  },

  replaceQueueWith (songs: Song | Song[]) {
    this.all = arrayify(songs)
  },

  queueAfterCurrent (songs: Song | Song[]) {
    songs = arrayify(songs)

    if (!this.current || !this.all.length) {
      return this.queue(songs)
    }

    // First we unqueue the songs to make sure there are no duplicates.
    this.unqueue(songs)

    const head = this.all.splice(0, this.indexOf(this.current) + 1)
    this.all = head.concat(reactive(songs), this.all)
  },

  unqueue (songs: Song | Song[]) {
    songs = arrayify(songs)
    songs.forEach(song => (song.playback_state = 'Stopped'))
    this.all = differenceBy(this.all, songs, 'id')
  },

  /**
   * Move some songs to after a target.
   */
  move (songs: Song | Song[], target: Song) {
    const targetIndex = this.indexOf(target)
    const movedSongs = arrayify(songs)

    movedSongs.forEach(song => {
      this.all.splice(this.indexOf(song), 1)
      this.all.splice(targetIndex, 0, reactive(song))
    })

    this.saveState()
  },

  clear () {
    this.all = []
  },

  /**
   * Clear the queue without saving the state.
   */
  clearSilently () {
    this.state.songs = []
  },

  indexOf (song: Song) {
    return this.all.indexOf(reactive(song))
  },

  get next () {
    if (!this.current) {
      return this.first
    }

    const index = this.indexOf(this.current) + 1

    return index >= this.all.length ? undefined : this.all[index]
  },

  get previous () {
    if (!this.current) {
      return this.last
    }

    const index = this.indexOf(this.current) - 1

    return index < 0 ? undefined : this.all[index]
  },

  get current () {
    return this.all.find(song => song.playback_state !== 'Stopped')
  },

  async fetchRandom (limit = 500) {
    this.all = await http.get<Song[]>(`queue/fetch?order=rand&limit=${limit}`)
    return this.all
  },

  async fetchInOrder (sortField: SongListSortField, order: SortOrder, limit = 500) {
    this.all = await http.get<Song[]>(`queue/fetch?order=${order}&sort=${sortField}&limit=${limit}`)
    return this.all
  },

  saveState () {
    try {
      http.silently.put('queue/state', { songs: this.state.songs.map(song => song.id) })
    } catch (e) {
      console.error(e)
    }
  }
}
