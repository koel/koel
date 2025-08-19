import { reactive } from 'vue'
import { http } from '@/services/http'
import { playableStore } from '@/stores/playableStore'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { isSong } from '@/utils/typeGuards'

export const overviewStore = {
  state: reactive({
    mostPlayedAlbums: [] as Album[],
    mostPlayedArtists: [] as Artist[],
    mostPlayedSongs: [] as Song[],
    recentlyAddedAlbums: [] as Album[],
    recentlyAddedSongs: [] as Song[],
    recentlyPlayed: [] as Playable[],
  }),

  async fetch () {
    const resource = await http.get<{
      most_played_albums: Album[]
      most_played_artists: Artist[]
      most_played_songs: Song[]
      recently_added_albums: Album[]
      recently_added_songs: Song[]
      recently_played_songs: Playable[]
    }>('overview')

    playableStore.syncWithVault(resource.most_played_songs)
    albumStore.syncWithVault(resource.recently_added_albums)
    artistStore.syncWithVault(resource.most_played_artists)

    this.state.mostPlayedAlbums = albumStore.syncWithVault(resource.most_played_albums)
    this.state.mostPlayedArtists = artistStore.syncWithVault(resource.most_played_artists)
    this.state.recentlyAddedAlbums = albumStore.syncWithVault(resource.recently_added_albums)
    this.state.recentlyAddedSongs = playableStore.syncWithVault(resource.recently_added_songs) as Song[]

    recentlyPlayedStore.excerptState.playables = playableStore.syncWithVault(resource.recently_played_songs)

    this.refreshPlayStats()
  },

  refreshPlayStats () {
    this.state.mostPlayedSongs = playableStore.getMostPlayedSongs(7)
    this.state.recentlyPlayed = recentlyPlayedStore.excerptState.playables.filter(playable => {
      if (isSong(playable) && playable.deleted) {
        return false
      }

      return playable.play_count > 0
    })
  },
}
