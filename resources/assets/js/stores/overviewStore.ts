import { reactive } from 'vue'
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

  async init () {
    const resource = await http.get<{
      most_played_songs: Song[],
      most_played_albums: Album[],
      most_played_artists: Artist[],
      recently_added_songs: Song[],
      recently_added_albums: Album[],
      recently_played_songs: Song[],
    }>('overview')

    songStore.syncWithVault([...resource.most_played_songs, ...resource.recently_added_songs])
    albumStore.syncWithVault([...resource.most_played_albums, ...resource.recently_added_albums])
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
    this.state.recentlyPlayed = recentlyPlayedStore.excerptState.songs.filter(song => !song.deleted)
  }
}
