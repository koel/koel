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
  }),

  async excerptSearch (q: string) {
    const result = await http.get<ExcerptSearchResult>(`search?q=${q}`)

    this.state.excerpt.playables = playableStore.syncWithVault(result.songs)
    this.state.excerpt.albums = albumStore.syncWithVault(result.albums)
    this.state.excerpt.artists = artistStore.syncWithVault(result.artists)
    this.state.excerpt.podcasts = result.podcasts
    this.state.excerpt.radio_stations = radioStationStore.sync(result.radio_stations)
  },

  async playableSearch (q: string) {
    this.state.playables = playableStore.syncWithVault(await http.get<Playable[]>(`search/songs?q=${q}`))
  },

  resetPlayableResultState () {
    this.state.playables = []
  },
}
