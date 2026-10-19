import { reactive } from 'vue'
import { debounce, differenceBy, unionBy } from 'lodash-es'
import { arrayify, moveItemsInList } from '@/utils/helpers'
import { http } from '@/services/http'
import { lastWriteWins } from '@/utils/lastWriteWins'
import { playableStore } from '@/stores/playableStore'

const saveQueueState = lastWriteWins((songs: Playable['id'][]) =>
  http.silently.withRetries.put('queue/state', { songs }),
)

export const queueStore = {
  state: reactive<{ playables: Playable[]; currentOutsideQueue: Playable | null }>({
    playables: [],
    currentOutsideQueue: null,
  }),

  init(savedState: QueueState) {
    // don't set this.all here, as it would trigger saving state
    this.replacePlayables(playableStore.syncWithVault(savedState.songs))

    if (!this.state.playables.length) {
      return
    }

    if (savedState.current_song) {
      const [currentPlayable] = playableStore.syncWithVault(savedState.current_song)
      currentPlayable.playback_state = 'Paused'
      this.state.currentOutsideQueue = this.contains(currentPlayable) ? null : currentPlayable
    } else {
      this.all[0].playback_state = 'Paused'
    }
  },

  get all() {
    return this.state.playables
  },

  set all(playables: Playable[]) {
    this.replacePlayables(playableStore.syncWithVault(playables))
    this.saveState()
  },

  get first() {
    return this.all[0]
  },

  get last() {
    return this.all[this.all.length - 1]
  },

  contains(playable: Playable) {
    return this.all.includes(reactive(playable))
  },

  /**
   * Add playable(s) to the end of the current queue.
   */
  queue(playables: MaybeArray<Playable>) {
    playables = arrayify(playables)
    this.all = unionBy(differenceBy(this.all, playables, 'id'), playables, 'id')
  },

  queueIfNotQueued(playable: Playable, position: 'top' | 'bottom' | 'after-current' = 'after-current') {
    if (this.contains(playable)) {
      return
    }

    switch (position) {
      case 'top':
        this.queueToTop(playable)
        break
      case 'bottom':
        this.queue(playable)
        break
      case 'after-current':
        this.queueAfterCurrent(playable)
        break
    }
  },

  queueToTop(playables: MaybeArray<Playable>) {
    this.all = unionBy(arrayify(playables), this.all, 'id')
  },

  replaceQueueWith(playables: MaybeArray<Playable>) {
    this.all = arrayify(playables)
  },

  queueAfterCurrent(playables: MaybeArray<Playable>) {
    playables = arrayify(playables)
    const current = this.current

    if (!current || !this.all.length) {
      return this.queue(playables)
    }

    const playablesToMove = playables.filter(({ id }) => id !== current.id)
    const remaining = differenceBy(this.all, playablesToMove, 'id')
    const head = remaining.splice(0, remaining.indexOf(reactive(current)) + 1)
    this.all = head.concat(reactive(playablesToMove), remaining)
  },

  unqueue(playables: MaybeArray<Playable>) {
    playables = arrayify(playables)
    playables.forEach(playable => (playable.playback_state = 'Stopped'))
    this.all = differenceBy(this.all, playables, 'id')
  },

  /**
   * The first playable after the current one that would still be queued once the given playables are removed.
   */
  findNextRemaining(playablesToRemove: Playable[]) {
    const removedIds = new Set(playablesToRemove.map(({ id }) => id))

    return this.all.slice(this.indexOf(this.current!) + 1).find(({ id }) => !removedIds.has(id))
  },

  /**
   * Move some songs to after a target.
   */
  move(playables: MaybeArray<Playable>, target: Playable, placement: Placement) {
    this.state.playables = moveItemsInList(this.state.playables, playables, target, placement)
    this.saveState()
  },

  clear() {
    this.all = []
  },

  /**
   * Clear the queue without saving the state.
   */
  clearSilently() {
    this.replacePlayables([])
  },

  /**
   * Swap the queued playables while still tracking the current playable if it's no longer queued.
   */
  replacePlayables(playables: Playable[]) {
    const current = this.current

    this.state.currentOutsideQueue = current && !playables.some(({ id }) => id === current.id) ? current : null
    this.state.playables = playables
  },

  indexOf(playable: Playable) {
    return this.all.indexOf(reactive(playable))
  },

  get next() {
    if (!this.current) {
      return this.first
    }

    const index = this.indexOf(this.current) + 1

    return index >= this.all.length ? undefined : this.all[index]
  },

  get previous() {
    if (!this.current) {
      return this.last
    }

    const index = this.indexOf(this.current) - 1

    return index < 0 ? undefined : this.all[index]
  },

  get current() {
    const currentInQueue = this.all.find(({ playback_state }) => playback_state !== 'Stopped')

    if (currentInQueue) {
      return currentInQueue
    }

    const { currentOutsideQueue } = this.state

    return currentOutsideQueue && currentOutsideQueue.playback_state !== 'Stopped' ? currentOutsideQueue : undefined
  },

  async fetchRandom(limit = 500) {
    this.all = await http.get<Song[]>(`queue/fetch?order=rand&limit=${limit}`)
    return this.all
  },

  async fetchInOrder(sortField: PlayableListSortField, order: SortOrder, limit = 500) {
    this.all = await http.get<Song[]>(`queue/fetch?order=${order}&sort=${sortField}&limit=${limit}`)
    return this.all
  },

  saveState() {
    saveQueueStateSoon()
  },
}

const saveQueueStateSoon = debounce(() => saveQueueState(queueStore.state.playables.map(({ id }) => id)), 1_000)

document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'hidden') {
    saveQueueStateSoon.flush()
  }
})
