import { reactive } from 'vue'
import { differenceBy, unionBy } from 'lodash'
import { arrayify, isSong, logger, moveItemsInList } from '@/utils'
import { http } from '@/services'
import { songStore } from '@/stores'

export const queueStore = {
  state: reactive<{ playables: Playable[] }>({
    playables: []
  }),

  init (savedState: QueueState) {
    // don't set this.all here, as it would trigger saving state
    this.state.playables = songStore.syncWithVault(savedState.songs)

    if (!this.state.playables.length) {
      return
    }

    if (savedState.current_song) {
      songStore.syncWithVault(savedState.current_song)[0].playback_state = 'Paused'
    } else {
      this.all[0].playback_state = 'Paused'
    }
  },

  get all () {
    return this.state.playables
  },

  set all (playables: Playable[]) {
    this.state.playables = playables
    songStore.syncWithVault(playables.filter(isSong))
    this.saveState()
  },

  get first () {
    return this.all[0]
  },

  get last () {
    return this.all[this.all.length - 1]
  },

  contains (playable: Playable) {
    return this.all.includes(reactive(playable))
  },

  /**
   * Add song(s) to the end of the current queue.
   */
  queue (playables: MaybeArray<Playable>) {
    this.unqueue(playables)
    this.all = unionBy(this.all, arrayify(playables), 'id')
  },

  queueIfNotQueued (playable: Playable) {
    if (!this.contains(playable)) {
      this.queueAfterCurrent(playable)
    }
  },

  queueToTop (playables: MaybeArray<Playable>) {
    this.all = unionBy(arrayify(playables), this.all, 'id')
  },

  replaceQueueWith (playables: MaybeArray<Playable>) {
    this.all = arrayify(playables)
  },

  queueAfterCurrent (playables: MaybeArray<Playable>) {
    playables = arrayify(playables)

    if (!this.current || !this.all.length) {
      return this.queue(playables)
    }

    // First we unqueue the songs to make sure there are no duplicates.
    this.unqueue(playables)

    const head = this.all.splice(0, this.indexOf(this.current) + 1)
    this.all = head.concat(reactive(playables), this.all)
  },

  unqueue (playables: MaybeArray<Playable>) {
    playables = arrayify(playables)
    playables.forEach(song => (song.playback_state = 'Stopped'))
    this.all = differenceBy(this.all, playables, 'id')
  },

  /**
   * Move some songs to after a target.
   */
  move (playables: MaybeArray<Playable>, target: Playable, type: MoveType) {
    this.state.playables = moveItemsInList(this.state.playables, playables, target, type)
    this.saveState()
  },

  clear () {
    this.all = []
  },

  /**
   * Clear the queue without saving the state.
   */
  clearSilently () {
    this.state.playables = []
  },

  indexOf (playable: Playable) {
    return this.all.indexOf(reactive(playable))
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
    return this.all.find(({ playback_state }) => playback_state !== 'Stopped')
  },

  async fetchRandom (limit = 500) {
    this.all = await http.get<Song[]>(`queue/fetch?order=rand&limit=${limit}`)
    return this.all
  },

  async fetchInOrder (sortField: PlayableListSortField, order: SortOrder, limit = 500) {
    this.all = await http.get<Song[]>(`queue/fetch?order=${order}&sort=${sortField}&limit=${limit}`)
    return this.all
  },

  saveState () {
    try {
      http.silently.put('queue/state', { songs: this.state.playables.map(({ id }) => id) })
    } catch (error: unknown) {
      logger.error(error)
    }
  }
}
