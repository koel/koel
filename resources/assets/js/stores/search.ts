import { http } from '@/services'
import { songStore } from '@/stores/song'
import { albumStore } from '@/stores/album'
import { artistStore } from '@/stores/artist'

interface ExcerptSearchResult {
  songs: Array<string>
  albums: Array<number>
  artists: Array<number>
}

interface SongSearchResult {
  songs: Array<string>
}

export const searchStore = {
  state: {
    excerpt: {
      songs: [] as Song[],
      albums: [] as Album[],
      artists: [] as Artist[]
    },
    songs: [] as Song[],
  },

  excerptSearch (q: string) {
    http.get<{ [key: string]: ExcerptSearchResult }>(`search?q=${q}`).then(({ results }) => {
      this.state.excerpt.songs = songStore.byIds(results.songs)
      this.state.excerpt.albums = albumStore.byIds(results.albums)
      this.state.excerpt.artists = artistStore.byIds(results.artists)
    })
  },

  songSearch (q: string) {
    http.get<SongSearchResult>(`search/songs?q=${q}`).then(({ songs }) => {
      this.state.songs = this.state.songs.concat(songStore.byIds(songs))
    })
  },

  resetSongResultState () {
    this.state.songs = []
  }
}
