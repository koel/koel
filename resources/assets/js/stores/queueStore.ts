import { reactive } from 'vue'
import { differenceBy, shuffle, union, unionBy } from 'lodash'
import { arrayify } from '@/utils'
import { httpService } from '@/services'
import { songStore } from '@/stores'

export const queueStore = {
  state: reactive({
    songs: [] as Song[],
    current: null as Song
  }),

  init () {
    // We don't have anything to do here yet.
    // How about another song then?
    //
    // LITTLE WING
    // -- Jimi Hendrix
    //
    // Well she's walking
    // Through the clouds
    // With a circus mind
    // That's running wild
    // Butterflies and zebras and moonbeams and fairy tales
    // That's all she ever thinks about
    // Riding with the wind
    //
    // When I'm sad
    // She comes to me
    // With a thousand smiles
    // She gives to me free
    // It's alright she said
    // It's alright
    // Take anything you want from me
    // Anything...
  },

  get all () {
    return this.state.songs
  },

  set all (songs: Song[]) {
    this.state.songs = songs
  },

  get first () {
    return this.all[0]
  },

  get last () {
    return this.all[this.all.length - 1]
  },

  contains (song: Song) {
    return this.all.includes(song)
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
    this.state.songs = arrayify(songs)
  },

  queueAfterCurrent (songs: Song | Song[]) {
    songs = arrayify(songs)

    if (!this.current || !this.all.length) {
      return this.queue(songs)
    }

    // First we unqueue the songs to make sure there are no duplicates.
    this.unqueue(songs)

    const head = this.all.splice(0, this.indexOf(this.current) + 1)
    this.all = head.concat(songs, this.all)
  },

  unqueue (songs: Song | Song[]) {
    this.all = differenceBy(this.all, arrayify(songs), 'id')
  },

  /**
   * Move some songs to after a target.
   */
  move (songs: Song | Song[], target: Song) {
    const targetIndex = this.indexOf(target)
    const movedSongs = arrayify(songs)

    movedSongs.forEach(song => {
      this.all.splice(this.indexOf(song), 1)
      this.all.splice(targetIndex, 0, song)
    })
  },

  clear () {
    this.all = []
  },

  indexOf (song: Song) {
    return this.all.indexOf(song)
  },

  get next () {
    if (!this.current) {
      return this.first
    }

    const index = this.all.map(song => song.id).indexOf(this.current.id) + 1

    return index >= this.all.length ? undefined : this.all[index]
  },

  get previous () {
    if (!this.current) {
      return this.last
    }

    const index = this.all.map(song => song.id).indexOf(this.current.id) - 1

    return index < 0 ? undefined : this.all[index]
  },

  get current () {
    return this.all.find(song => song.playback_state !== 'Stopped')
  },

  shuffle () {
    this.all = shuffle(this.all)
  },

  async fetchRandom (limit = 500) {
    const songs = await httpService.get<Song[]>(`queue/fetch?order=rand&limit=${limit}`)
    this.state.songs = songStore.syncWithVault(songs)
  },

  async fetchInOrder (sortField: SongListSortField, order: SortOrder, limit = 500) {
    const songs = await httpService.get<Song[]>(`queue/fetch?order=${order}&sort=${sortField}&limit=${limit}`)
    this.state.songs = songStore.syncWithVault(songs)
  }
}
