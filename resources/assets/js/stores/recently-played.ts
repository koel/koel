import { songStore } from '.'
import { http } from '@/services'
import { remove } from 'lodash'

const EXCERPT_COUNT = 7

export const recentlyPlayedStore = {
  excerptState: {
    songs: [] as Song[]
  },

  state: {
    songs: [] as Song[]
  },

  fetched: false,

  initExcerpt (songIds: string[]): void {
    this.excerptState.songs = songStore.byIds(songIds)
  },

  async fetchAll (): Promise<Song[]> {
    if (!this.fetched) {
      this.state.songs = songStore.byIds(await http.get<string[]>(`interaction/recently-played`))
      this.fetched = true
    }

    return this.state.songs
  },

  add (song: Song): void {
    [this.state, this.excerptState].forEach((state): void => {
      // make sure there's no duplicate
      remove(state.songs, s => s.id === song.id)
      state.songs.unshift(song)
    })

    this.excerptState.songs.splice(EXCERPT_COUNT)
  }
}
