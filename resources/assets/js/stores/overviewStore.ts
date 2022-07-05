import { reactive } from 'vue'
import { httpService } from '@/services'
import { songStore } from '@/stores/songStore'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { recentlyPlayedStore } from '@/stores'

export const overviewStore = {
  state: reactive({
    recentlyPlayed: [],
    recentlyAddedSongs: [],
    recentlyAddedAlbums: [],
    mostPlayedSongs: [],
    mostPlayedAlbums: [],
    mostPlayedArtists: []
  }),

  async init () {
    const resource = await httpService.get<{
      most_played_songs: Song[],
      most_played_albums: Album[],
      most_played_artists: Artist[],
      recently_added_songs: Song[],
      recently_added_albums: Album[],
      recently_played_songs: Song[],
    }>('overview')

    songStore.syncWithVault(resource.most_played_songs)
    songStore.syncWithVault(resource.recently_added_songs)
    albumStore.syncWithVault(resource.recently_added_albums)
    albumStore.syncWithVault(resource.most_played_albums)
    artistStore.syncWithVault(resource.most_played_artists)

    recentlyPlayedStore.excerptState.songs = songStore.syncWithVault(resource.recently_played_songs)

    this.refresh()
  },

  refresh () {
    this.state.mostPlayedSongs = songStore.getMostPlayed(7)
    this.state.mostPlayedAlbums = albumStore.getMostPlayed(6)
    this.state.mostPlayedArtists = artistStore.getMostPlayed(6)
    this.state.recentlyAddedSongs = songStore.getRecentlyAdded(9)
    this.state.recentlyAddedAlbums = albumStore.getRecentlyAdded(6)

    this.state.recentlyPlayed = recentlyPlayedStore.excerptState.songs
  }
}
