import { union, difference, shuffle } from 'lodash'

export const queueStore = {
  state: {
    songs: [] as Song[],
    current: undefined as Song | undefined
  },

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
    // Butterflies and zebras and moonbeams and fairytales
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

  get all (): Song[] {
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

  contains (song: Song): boolean {
    return this.all.includes(song)
  },

  /**
   * Add a list of songs to the end of the current queue.
   * @param {Song|Song[]} songs The song, or an array of songs
   */
  queue (songs: Song | Song[]): void {
    this.unqueue(songs)
    this.all = union(this.all, (<Song[]>[]).concat(songs))
  },

  /**
   * Add a list of songs to the top of the current queue.
   * @param {Song|Song[]} songs The song, or an array of songs
   */
  queueToTop (songs: Song | Song[]): void {
    this.all = union((<Song[]>[]).concat(songs), this.all)
  },

  /**
   * Replace the current queue with a list of songs.
   * @param {Song|Song[]} songs The song, or an array of songs
   */
  replaceQueueWith (songs: Song | Song[]): void {
    this.all = (<Song[]>[]).concat(songs)
  },

  /**
   * Queue songs right after the currently played song.
   * @param {Song|Song[]} songs The song, or an array of songs
   */
  queueAfterCurrent (songs: Song | Song[]): void {
    songs = (<Song[]>[]).concat(songs)

    if (!this.current || !this.all.length) {
      return this.queue(songs)
    }

    // First we unqueue the songs to make sure there are no duplicates.
    this.unqueue(songs)

    const head = this.all.splice(0, this.indexOf(this.current) + 1)
    this.all = head.concat(songs, this.all)
  },

  unqueue (songs: Song | Song[]): void {
    this.all = difference(this.all, (<Song[]>[]).concat(songs))
  },

  /**
   * Move some songs to after a target.
   *
   * @param {Song|Song[]} songs The song, or an array of songs
   * @param {Song}     target The target song object
   */
  move (songs: Song | Song[], target: Song): void {
    const targetIndex = this.indexOf(target)
    const movedSongs = (<Song[]>[]).concat(songs)

    movedSongs.forEach(song => {
      this.all.splice(this.indexOf(song), 1)
      this.all.splice(targetIndex, 0, song)
    })
  },

  clear (): void {
    this.all = []
  },

  indexOf (song: Song): number {
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
    return this.state.current
  },

  set current (song) {
    this.state.current = song
  },

  shuffle (): void {
    this.all = shuffle(this.all)
  }
}
