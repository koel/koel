import { head, last, each, includes, union, difference, map, shuffle as _shuffle, first } from 'lodash'

export const queueStore = {
  state: {
    songs: [],
    current: null
  },

  init () {
    // We don't have anything to do here yet.
    // How about another song then?
    //
    // LITTLE WING
    // -- by Jimi Fucking Hendrix
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

  /**
   * Get all queued songs.
   *
   * @return {Array.<Object>}
   */
  get all () {
    return this.state.songs
  },

  /**
   * Set all queued songs.
   *
   * @param {Array.<Object>}
   */
  set all (songs) {
    this.state.songs = songs
  },

  /**
   * The first song in the queue.
   *
   * @return {?Object}
   */
  get first () {
    return head(this.all)
  },

  /**
   * The last song in the queue.
   *
   * @return {?Object}
   */
  get last () {
    return last(this.all)
  },

  /**
   * Determine if the queue contains a song.
   *
   * @param  {Object} song
   *
   * @return {Boolean}
   */
  contains (song) {
    return includes(this.all, song)
  },

  /**
   * Add a list of songs to the end of the current queue,
   * or replace the current queue as a whole if `replace` is true.
   *
   * @param {Object|Array.<Object>}   songs   The song, or an array of songs
   * @param {Boolean}         replace Whether to replace the current queue
   * @param {Boolean}         toTop   Whether to prepend or append to the queue
   */
  queue (songs, replace = false, toTop = false) {
    songs = [].concat(songs)

    if (replace) {
      this.all = songs
    } else {
      this.all = toTop ? union(songs, this.all) : union(this.all, songs)
    }
  },

  /**
   * Queue song(s) to after the current song.
   *
   * @param  {Array.<Object>|Object} songs
   */
  queueAfterCurrent (songs) {
    songs = [].concat(songs)

    if (!this.current || !this.all.length) {
      return this.queue(songs)
    }

    // First we unqueue the songs to make sure there are no duplicates.
    this.unqueue(songs)

    const head = this.all.splice(0, this.indexOf(this.current) + 1)
    this.all = head.concat(songs, this.all)
  },

  /**
   * Unqueue a song, or several songs at once.
   *
   * @param  {Object|String|Array.<Object>} songs The song(s) to unqueue
   */
  unqueue (songs) {
    this.all = difference(this.all, [].concat(songs))
  },

  /**
   * Move some songs to after a target.
   *
   * @param  {Array.<Object>} songs  Songs to move
   * @param  {Object}     target The target song object
   */
  move (songs, target) {
    const $targetIndex = this.indexOf(target)

    each(songs, song => {
      this.all.splice(this.indexOf(song), 1)
      this.all.splice($targetIndex, 0, song)
    })
  },

  /**
   * Clear the current queue.
   */
  clear () {
    this.all = []
  },

  /**
   * Get index of a song in the queue.
   *
   * @param  {Object} song
   *
   * @return {?Integer}
   */
  indexOf (song) {
    return this.all.indexOf(song)
  },

  /**
   * The next song in queue.
   *
   * @return {?Object}
   */
  get next () {
    if (!this.current) {
      return first(this.all)
    }

    const index = map(this.all, 'id').indexOf(this.current.id) + 1

    return index >= this.all.length ? null : this.all[index]
  },

  /**
   * The previous song in queue.
   *
   * @return {?Object}
   */
  get previous () {
    if (!this.current) {
      return last(this.all)
    }

    const index = map(this.all, 'id').indexOf(this.current.id) - 1

    return index < 0 ? null : this.all[index]
  },

  /**
   * The current song.
   *
   * @return {Object}
   */
  get current () {
    return this.state.current
  },

  /**
   * Set a song as the current queued song.
   *
   * @param  {Object} song
   *
   * @return {Object} The queued song.
   */
  set current (song) {
    this.state.current = song
    return this.state.current
  },

  /**
   * Shuffle the queue.
   *
   * @return {Array.<Object>} The shuffled array of song objects
   */
  shuffle () {
    this.all = _shuffle(this.all)
    return this.all
  }
}
