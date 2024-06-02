import { reactive, watch } from 'vue'
import { http } from '@/services'
import { songStore } from '@/stores/songStore'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { recentlyPlayedStore } from '@/stores'
import { isEpisode, isSong } from '@/utils'

export const overviewStore = {
  state: reactive({
    recentlyPlayed: [] as Playable[],
    recentlyAddedSongs: [] as Song[],
    recentlyAddedAlbums: [] as Album[],
    mostPlayedSongs: [] as Playable[],
    mostPlayedAlbums: [] as Album[],
    mostPlayedArtists: [] as Artist[]
  }),

  async fetch () {
    const resource = await http.get<{
      most_played_songs: Playable[],
      most_played_albums: Album[],
      most_played_artists: Artist[],
      recently_added_songs: Song[],
      recently_added_albums: Album[],
      recently_played_songs: Playable[],
    }>('overview')

    songStore.syncWithVault(resource.most_played_songs)
    albumStore.syncWithVault(resource.recently_added_albums)
    artistStore.syncWithVault(resource.most_played_artists)

    this.state.mostPlayedAlbums = albumStore.syncWithVault(resource.most_played_albums)
    this.state.mostPlayedArtists = artistStore.syncWithVault(resource.most_played_artists)
    this.state.recentlyAddedSongs = songStore.syncWithVault(resource.recently_added_songs) as Song[]
    this.state.recentlyAddedAlbums = albumStore.syncWithVault(resource.recently_added_albums)

    recentlyPlayedStore.excerptState.playables = songStore.syncWithVault(resource.recently_played_songs)

    this.refreshPlayStats()
  },

  refreshPlayStats () {
    this.state.mostPlayedSongs = songStore.getMostPlayed(7)
    this.state.recentlyPlayed = recentlyPlayedStore.excerptState.playables.filter(playable => {
      if (isSong(playable) && playable.deleted) return false
      return playable.play_count > 0
    })
  }
}
