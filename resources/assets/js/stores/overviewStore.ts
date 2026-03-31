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
    recentlyAddedArtists: [] as Artist[],
    recentlyAddedSongs: [] as Song[],
    recentlyPlayed: [] as Playable[],
    leastPlayedSongs: [] as Song[],
    randomSongs: [] as Song[],
    similarSongs: [] as Song[],
  }),

  async fetch() {
    const resource = await http.get<{
      most_played_albums: Album[]
      most_played_artists: Artist[]
      most_played_songs: Song[]
      recently_added_albums: Album[]
      recently_added_artists: Artist[]
      recently_added_songs: Song[]
      recently_played_songs: Playable[]
      least_played_songs: Song[]
      random_songs: Song[]
      similar_songs: Song[]
    }>('overview')

    playableStore.syncWithVault(resource.most_played_songs)

    this.state.mostPlayedAlbums = albumStore.syncWithVault(resource.most_played_albums)
    this.state.mostPlayedArtists = artistStore.syncWithVault(resource.most_played_artists)
    this.state.recentlyAddedAlbums = albumStore.syncWithVault(resource.recently_added_albums)
    this.state.recentlyAddedArtists = artistStore.syncWithVault(resource.recently_added_artists)
    this.state.recentlyAddedSongs = playableStore.syncWithVault(resource.recently_added_songs) as Song[]
    this.state.leastPlayedSongs = playableStore.syncWithVault(resource.least_played_songs) as Song[]
    this.state.randomSongs = playableStore.syncWithVault(resource.random_songs) as Song[]
    this.state.similarSongs = playableStore.syncWithVault(resource.similar_songs) as Song[]

    recentlyPlayedStore.excerptState.playables = playableStore.syncWithVault(resource.recently_played_songs)

    this.refreshPlayStats()
  },

  async refreshRandomSongs() {
    const songs = playableStore.syncWithVault(await http.get<Song[]>('queue/fetch?order=rand&limit=6')) as Song[]

    this.state.randomSongs = songs

    return songs
  },

  refreshPlayStats() {
    this.state.mostPlayedSongs = playableStore.getMostPlayedSongs(6)
    this.state.recentlyPlayed = recentlyPlayedStore.excerptState.playables
      .filter(playable => {
        if (isSong(playable) && playable.deleted) {
          return false
        }

        return playable.play_count > 0
      })
      .slice(0, 6)
  },
}
