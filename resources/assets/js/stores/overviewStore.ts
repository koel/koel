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

    songStore.syncWithVault(resource.most_played_songs)
    albumStore.syncWithVault(resource.recently_added_albums)
    artistStore.syncWithVault(resource.most_played_artists)

    this.state.mostPlayedAlbums = albumStore.syncWithVault(resource.most_played_albums)
    this.state.mostPlayedArtists = artistStore.syncWithVault(resource.most_played_artists)
    this.state.recentlyAddedSongs = songStore.syncWithVault(resource.recently_added_songs)
    this.state.recentlyAddedAlbums = albumStore.syncWithVault(resource.recently_added_albums)

    recentlyPlayedStore.excerptState.songs = songStore.syncWithVault(resource.recently_played_songs)

    this.refresh()
  },

  refresh () {
    // @since v6.2.3
    // To keep things simple, we only refresh the song stats.
    // All album/artist stats are simply ignored.
    this.state.mostPlayedSongs = songStore.getMostPlayed(7)
    this.state.recentlyPlayed = recentlyPlayedStore.excerptState.songs.filter(song => !song.deleted)
  }
}
