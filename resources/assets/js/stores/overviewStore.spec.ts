import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { http } from '@/services/http'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { overviewStore } from '@/stores/overviewStore'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { songStore } from '@/stores/songStore'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      overviewStore.state = {
        recentlyPlayed: [],
        recentlyAddedSongs: [],
        recentlyAddedAlbums: [],
        mostPlayedSongs: [],
        mostPlayedAlbums: [],
        mostPlayedArtists: [],
      }
    })
  }

  protected test () {
    it('initializes the store', async () => {
      const songSyncMock = this.mock(songStore, 'syncWithVault')
      const albumSyncMock = this.mock(albumStore, 'syncWithVault')
      const artistSyncMock = this.mock(artistStore, 'syncWithVault')
      const refreshMock = this.mock(overviewStore, 'refreshPlayStats')

      const mostPlayedSongs = factory('song', 7)
      const mostPlayedAlbums = factory('album', 6)
      const mostPlayedArtists = factory('artist', 6)
      const recentlyAddedSongs = factory('song', 9)
      const recentlyAddedAlbums = factory('album', 6)
      const recentlyPlayedSongs = factory('song', 9)

      const getMock = this.mock(http, 'get').mockResolvedValueOnce({
        most_played_songs: mostPlayedSongs,
        most_played_albums: mostPlayedAlbums,
        most_played_artists: mostPlayedArtists,
        recently_added_songs: recentlyAddedSongs,
        recently_added_albums: recentlyAddedAlbums,
        recently_played_songs: recentlyPlayedSongs,
      })

      await overviewStore.fetch()

      expect(getMock).toHaveBeenCalledWith('overview')
      expect(songSyncMock).toHaveBeenNthCalledWith(1, mostPlayedSongs)
      expect(songSyncMock).toHaveBeenNthCalledWith(2, recentlyAddedSongs)
      expect(songSyncMock).toHaveBeenNthCalledWith(3, recentlyPlayedSongs)
      expect(albumSyncMock).toHaveBeenNthCalledWith(1, recentlyAddedAlbums)
      expect(albumSyncMock).toHaveBeenNthCalledWith(2, mostPlayedAlbums)
      expect(artistSyncMock).toHaveBeenCalledWith(mostPlayedArtists)
      expect(refreshMock).toHaveBeenCalled()
    })

    it('refreshes the store', () => {
      const mostPlayedSongs = factory('song', 7)
      const recentlyPlayedSongs = factory('song', 9)

      const mostPlayedSongsMock = this.mock(songStore, 'getMostPlayed', mostPlayedSongs)
      recentlyPlayedStore.excerptState.playables = recentlyPlayedSongs

      overviewStore.refreshPlayStats()

      expect(mostPlayedSongsMock).toHaveBeenCalled()

      expect(overviewStore.state.recentlyPlayed).toEqual(recentlyPlayedSongs)
      expect(overviewStore.state.mostPlayedSongs).toEqual(mostPlayedSongs)
    })
  }
}
