import { reactive } from 'vue'
import { httpService } from '@/services'
import { albumStore, artistStore, songStore } from '@/stores'

type ExcerptState = {
  songs: Song[],
  albums: Album[],
  artists: Artist[]
}

export type ExcerptSearchResult = ExcerptState

export const searchStore = {
  state: reactive({
    excerpt: {
      songs: [],
      albums: [],
      artists: []
    } as ExcerptState,
    songs: [] as Song[]
  }),

  async excerptSearch (q: string) {
    const result = await httpService.get<ExcerptSearchResult>(`search?q=${q}`)

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
