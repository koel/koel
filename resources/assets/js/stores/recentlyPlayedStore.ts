import { reactive } from 'vue'
import { http } from '@/services/http'
import { playableStore } from '@/stores/playableStore'

const EXCERPT_COUNT = 6

const moveToTop = (playables: Playable[], playable: Playable) => [
  playable,
  ...playables.filter(({ id }) => id !== playable.id),
]

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
    this.excerptState.playables = moveToTop(this.excerptState.playables, playable).slice(0, EXCERPT_COUNT)

    if (!this.state.playables.length) {
      await this.fetch()
    }

    this.state.playables = moveToTop(this.state.playables, playable)
  },
}
