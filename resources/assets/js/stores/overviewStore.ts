import { reactive, watch } from 'vue'
import { http } from '@/services'
import { songStore } from '@/stores/songStore'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { recentlyPlayedStore } from '@/stores'

export const overviewStore = {
  state: reactive({
    recentlyPlayed: [] as Song[],
    recentlyAddedSongs: [] as Song[],
    recentlyAddedAlbums: [] as Album[],
    mostPlayedSongs: [] as Song[],
    mostPlayedAlbums: [] as Album[],
    mostPlayedArtists: [] as Artist[]
  }),

  async fetch () {
    const resource = await http.get<{
      most_played_songs: Song[],
      most_played_albums: Album[],
      most_played_artists: Artist[],
      recently_added_songs: Song[],
      recently_added_albums: Album[],
      recently_played_songs: Song[],
    }>('overview')

    songStore.syncWithVault(resource.most_played_songs)
    albumStore.syncWithVault(resource.recently_added_albums)
    artistStore.syncWithVault(resource.most_played_artists)

    this.state.mostPlayedAlbums = albumStore.syncWithVault(resource.most_played_albums)
    this.state.mostPlayedArtists = artistStore.syncWithVault(resource.most_played_artists)
    this.state.recentlyAddedSongs = songStore.syncWithVault(resource.recently_added_songs)
    this.state.recentlyAddedAlbums = albumStore.syncWithVault(resource.recently_added_albums)

    recentlyPlayedStore.excerptState.playables = songStore.syncWithVault(resource.recently_played_songs)

    this.refreshPlayStats()
  },

  refreshPlayStats () {
    this.state.mostPlayedSongs = songStore.getMostPlayed(7)
    this.state.recentlyPlayed = recentlyPlayedStore.excerptState.playables.filter(
      ({ deleted, play_count }) => !deleted && play_count > 0
    )
  }
}
