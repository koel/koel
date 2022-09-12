import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { httpService } from '@/services'
import { albumStore, artistStore, overviewStore, recentlyPlayedStore, songStore } from '@/stores'

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
      const refreshMock = this.mock(overviewStore, 'refresh')

      const mostPlayedSongs = factory<Song>('song', 7)
      const mostPlayedAlbums = factory<Album>('album', 6)
      const mostPlayedArtists = factory<Artist>('artist', 6)
      const recentlyAddedSongs = factory<Song>('song', 9)
      const recentlyAddedAlbums = factory<Album>('album', 6)
      const recentlyPlayedSongs = factory<Song>('song', 9)

      const getMock = this.mock(httpService, 'get').mockResolvedValueOnce({
        most_played_songs: mostPlayedSongs,
        most_played_albums: mostPlayedAlbums,
        most_played_artists: mostPlayedArtists,
        recently_added_songs: recentlyAddedSongs,
        recently_added_albums: recentlyAddedAlbums,
        recently_played_songs: recentlyPlayedSongs
      })

      await overviewStore.init()

      expect(getMock).toHaveBeenCalledWith('overview')
      expect(songSyncMock).toHaveBeenNthCalledWith(1, [...mostPlayedSongs, ...recentlyAddedSongs])
      expect(songSyncMock).toHaveBeenNthCalledWith(2, recentlyPlayedSongs)
      expect(albumSyncMock).toHaveBeenCalledWith([...mostPlayedAlbums, ...recentlyAddedAlbums])
      expect(artistSyncMock).toHaveBeenCalledWith(mostPlayedArtists)
      expect(refreshMock).toHaveBeenCalled()
    })

    it('refreshes the store', () => {
      const mostPlayedSongs = factory<Song>('song', 7)
      const mostPlayedAlbums = factory<Album>('album', 6)
      const mostPlayedArtists = factory<Artist>('artist', 6)
      const recentlyAddedSongs = factory<Song>('song', 9)
      const recentlyAddedAlbums = factory<Album>('album', 6)
      const recentlyPlayedSongs = factory<Song>('song', 9)

      const mostPlayedSongsMock = this.mock(songStore, 'getMostPlayed', mostPlayedSongs)
      const mostPlayedAlbumsMock = this.mock(albumStore, 'getMostPlayed', mostPlayedAlbums)
      const mostPlayedArtistsMock = this.mock(artistStore, 'getMostPlayed', mostPlayedArtists)
      const recentlyAddedSongsMock = this.mock(songStore, 'getRecentlyAdded', recentlyAddedSongs)
      const recentlyAddedAlbumsMock = this.mock(albumStore, 'getRecentlyAdded', recentlyAddedAlbums)
      recentlyPlayedStore.excerptState.songs = recentlyPlayedSongs

      overviewStore.refresh()

      expect(mostPlayedSongsMock).toHaveBeenCalled()
      expect(mostPlayedAlbumsMock).toHaveBeenCalled()
      expect(mostPlayedArtistsMock).toHaveBeenCalled()
      expect(recentlyAddedSongsMock).toHaveBeenCalled()
      expect(recentlyAddedAlbumsMock).toHaveBeenCalled()

      expect(overviewStore.state.recentlyPlayed).toEqual(recentlyPlayedSongs)
      expect(overviewStore.state.recentlyAddedSongs).toEqual(recentlyAddedSongs)
      expect(overviewStore.state.recentlyAddedAlbums).toEqual(recentlyAddedAlbums)
      expect(overviewStore.state.mostPlayedSongs).toEqual(mostPlayedSongs)
      expect(overviewStore.state.mostPlayedAlbums).toEqual(mostPlayedAlbums)
      expect(overviewStore.state.mostPlayedArtists).toEqual(mostPlayedArtists)
    })
  }
}
