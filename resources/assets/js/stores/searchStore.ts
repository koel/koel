import { reactive } from 'vue'
import { httpService } from '@/services'
import { albumStore, artistStore, songStore } from '@/stores'

interface ExcerptSearchResult {
  songs: Array<string>
  albums: Array<number>
  artists: Array<number>
}

interface SongSearchResult {
  songs: Array<string>
}

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
    const { results } = await httpService.get<{ [key: string]: ExcerptSearchResult }>(`search?q=${q}`)
    this.state.excerpt.songs = songStore.byIds(results.songs)
    this.state.excerpt.albums = albumStore.byIds(results.albums)
    this.state.excerpt.artists = artistStore.byIds(results.artists)
  },

  async songSearch (q: string) {
    const { songs } = await httpService.get<SongSearchResult>(`search/songs?q=${q}`)
    this.state.songs = this.state.songs.concat(songStore.byIds(songs))
  },

  resetSongResultState () {
    this.state.songs = []
  }
}
