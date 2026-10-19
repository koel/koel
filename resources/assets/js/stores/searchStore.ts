import { reactive } from 'vue'
import { http } from '@/services/http'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'
import { radioStationStore } from '@/stores/radioStationStore'

export interface ExcerptState {
  playables: Playable[]
  albums: Album[]
  artists: Artist[]
  podcasts: Podcast[]
  radio_stations: RadioStation[]
}

export interface ExcerptSearchResult {
  songs: Playable[] // backward compatibility
  albums: Album[]
  artists: Artist[]
  podcasts: Podcast[]
  radio_stations: RadioStation[]
}

let latestExcerptSearchId = 0

export const searchStore = {
  state: reactive({
    excerpt: {
      playables: [],
      albums: [],
      artists: [],
      podcasts: [],
      radio_stations: [],
    } as ExcerptState,
    playables: [] as Playable[],
    keywords: '',
  }),

  async excerptSearch(q: string) {
    const searchId = ++latestExcerptSearchId
    const result = await http.get<ExcerptSearchResult>(`search?${new URLSearchParams({ q })}`)

    if (searchId !== latestExcerptSearchId) {
      return
    }

    this.state.excerpt.playables = playableStore.syncWithVault(result.songs)
    this.state.excerpt.albums = albumStore.syncWithVault(result.albums)
    this.state.excerpt.artists = artistStore.syncWithVault(result.artists)
    this.state.excerpt.podcasts = result.podcasts
    this.state.excerpt.radio_stations = radioStationStore.sync(result.radio_stations)
  },

  async playableSearch(q: string) {
    this.state.playables = playableStore.syncWithVault(
      await http.get<Playable[]>(`search/songs?${new URLSearchParams({ q })}`),
    )
  },

  resetPlayableResultState() {
    this.state.playables = []
  },
}
