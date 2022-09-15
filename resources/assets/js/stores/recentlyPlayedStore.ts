import { reactive } from 'vue'
import { http } from '@/services'
import { remove } from 'lodash'
import { songStore } from '@/stores'

const EXCERPT_COUNT = 7

export const recentlyPlayedStore = {
  excerptState: reactive({
    songs: [] as Song[]
  }),

  state: reactive({
    songs: [] as Song[]
  }),

  async fetch () {
    this.state.songs = songStore.syncWithVault(await http.get<Song[]>('songs/recently-played'))
  },

  async add (song: Song) {
    if (!this.state.songs.length) {
      await this.fetch()
    }

    [this.state, this.excerptState].forEach(state => {
      // make sure there's no duplicate
      remove(state.songs, s => s.id === song.id)
      state.songs.unshift(song)
    })

    this.excerptState.songs.splice(EXCERPT_COUNT)
  }
}
