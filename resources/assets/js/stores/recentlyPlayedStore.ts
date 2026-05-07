import { reactive } from 'vue'
import { http } from '@/services/http'
import { playableStore } from '@/stores/playableStore'

const EXCERPT_COUNT = 6

export const recentlyPlayedStore = {
  excerptState: reactive({
    playables: [] as Playable[],
  }),

  state: reactive({
    playables: [] as Playable[],
  }),

  async fetch() {
    this.state.playables = playableStore.syncWithVault(await http.get<Playable[]>('songs/recently-played'))
    return this.state.playables
  },

  async add(playable: Playable) {
    if (!this.state.playables.length) {
      await this.fetch()
    }

    ;[this.state, this.excerptState].forEach(state => {
      state.playables = state.playables.filter(s => s.id !== playable.id)
      state.playables.unshift(playable)
    })

    this.excerptState.playables.splice(EXCERPT_COUNT)
  },
}
