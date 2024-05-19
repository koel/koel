import { reactive } from 'vue'
import { http } from '@/services'
import { albumStore, artistStore, podcastStore, songStore } from '@/stores'

type ExcerptState = {
  playables: Playable[]
  albums: Album[]
  artists: Artist[]
  podcasts: Podcast[]
}

export type ExcerptSearchResult = {
  songs: Playable[] // backward compatibility
  albums: Album[]
  artists: Artist[]
  podcasts: Podcast[]
}

export const searchStore = {
  state: reactive({
    excerpt: {
      playables: [],
      albums: [],
      artists: [],
      podcasts: []
    } as ExcerptState,
    songs: [] as Playable[]
  }),

  async excerptSearch (q: string) {
    const result = await http.get<ExcerptSearchResult>(`search?q=${q}`)

    this.state.excerpt.playables = songStore.syncWithVault(result.songs)
    this.state.excerpt.albums = albumStore.syncWithVault(result.albums)
    this.state.excerpt.artists = artistStore.syncWithVault(result.artists)
    this.state.excerpt.podcasts = result.podcasts
  },

  async songSearch (q: string) {
    this.state.songs = songStore.syncWithVault(await http.get<Song[]>(`search/songs?q=${q}`))
  },

  resetSongResultState () {
    this.state.songs = []
  }
}
