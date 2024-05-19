import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { http } from '@/services'
import { albumStore, artistStore, overviewStore, recentlyPlayedStore, songStore } from '.'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      overviewStore.state = {
        recentlyPlayed: [],
        recentlyAddedSongs: [],
        recentlyAddedAlbums: [],
        mostPlayedSongs: [],
        mostPlayedAlbums: [],
        mostPlayedArtists: []
      }
    })
  }

  protected test () {
    it('initializes the store', async () => {
      const songSyncMock = this.mock(songStore, 'syncWithVault')
      const albumSyncMock = this.mock(albumStore, 'syncWithVault')
      const artistSyncMock = this.mock(artistStore, 'syncWithVault')
      const refreshMock = this.mock(overviewStore, 'refreshPlayStats')

      const mostPlayedSongs = factory<Song>('song', 7)
      const mostPlayedAlbums = factory<Album>('album', 6)
      const mostPlayedArtists = factory<Artist>('artist', 6)
      const recentlyAddedSongs = factory<Song>('song', 9)
      const recentlyAddedAlbums = factory<Album>('album', 6)
      const recentlyPlayedSongs = factory<Song>('song', 9)

      const getMock = this.mock(http, 'get').mockResolvedValueOnce({
        most_played_songs: mostPlayedSongs,
        most_played_albums: mostPlayedAlbums,
        most_played_artists: mostPlayedArtists,
        recently_added_songs: recentlyAddedSongs,
        recently_added_albums: recentlyAddedAlbums,
        recently_played_songs: recentlyPlayedSongs
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
      const mostPlayedSongs = factory<Song>('song', 7)
      const recentlyPlayedSongs = factory<Song>('song', 9)

      const mostPlayedSongsMock = this.mock(songStore, 'getMostPlayed', mostPlayedSongs)
      recentlyPlayedStore.excerptState.playables = recentlyPlayedSongs

      overviewStore.refreshPlayStats()

      expect(mostPlayedSongsMock).toHaveBeenCalled()

      expect(overviewStore.state.recentlyPlayed).toEqual(recentlyPlayedSongs)
      expect(overviewStore.state.mostPlayedSongs).toEqual(mostPlayedSongs)
    })
  }
}
