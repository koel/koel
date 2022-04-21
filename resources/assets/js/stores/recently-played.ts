import { songStore } from '.'
import { http } from '@/services'
import { remove } from 'lodash'
import { reactive } from 'vue'

const EXCERPT_COUNT = 7

export const recentlyPlayedStore = {
  excerptState: reactive({
    songs: [] as Song[]
  }),

  state: reactive({
    songs: [] as Song[]
  }),

  fetched: false,

  initExcerpt (songIds: string[]) {
    this.excerptState.songs = songStore.byIds(songIds)
  },

  async fetchAll () {
    if (!this.fetched) {
      this.state.songs = songStore.byIds(await http.get<string[]>(`interaction/recently-played`))
      this.fetched = true
    }

    return this.state.songs
  },

  add (song: Song) {
    [this.state, this.excerptState].forEach(state => {
      // make sure there's no duplicate
      remove(state.songs, s => s.id === song.id)
      state.songs.unshift(song)
    })

    this.excerptState.songs.splice(EXCERPT_COUNT)
  }
}
