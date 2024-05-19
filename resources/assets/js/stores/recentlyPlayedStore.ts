import { reactive } from 'vue'
import { http } from '@/services'
import { remove } from 'lodash'
import { songStore } from '@/stores'

const EXCERPT_COUNT = 7

export const recentlyPlayedStore = {
  excerptState: reactive({
    playables: [] as Playable[]
  }),

  state: reactive({
    playables: [] as Playable[]
  }),

  async fetch () {
    this.state.playables = songStore.syncWithVault(await http.get<Playable[]>('songs/recently-played'))
    return this.state.playables
  },

  async add (playable: Playable) {
    if (!this.state.playables.length) {
      await this.fetch()
    }

    [this.state, this.excerptState].forEach(state => {
      // make sure there's no duplicate
      remove(state.playables, s => s.id === playable.id)
      state.playables.unshift(playable)
    })

    this.excerptState.playables.splice(EXCERPT_COUNT)
  }
}
