import { reactive } from 'vue'
import { httpService } from '@/services'
import { albumStore, artistStore, songStore } from '@/stores'

export const searchStore = {
  state: reactive({
    excerpt: {
      songs: [] as Song[],
      albums: [] as Album[],
      artists: [] as Artist[]
    },
    songs: [] as Song[]
  }),

  async excerptSearch (q: string) {
    const result = await httpService.get<{
      songs: Song[]
      albums: Album[]
      artists: Artist[]
    }>(`search?q=${q}`)

    this.state.excerpt.songs = songStore.syncWithVault(result.songs)
    this.state.excerpt.albums = albumStore.syncWithVault(result.albums)
    this.state.excerpt.artists = artistStore.syncWithVault(result.artists)
  },

  async songSearch (q: string) {
    this.state.songs = songStore.syncWithVault(await httpService.get<Song[]>(`search/songs?q=${q}`))
  },

  resetSongResultState () {
    this.state.songs = []
  }
}
