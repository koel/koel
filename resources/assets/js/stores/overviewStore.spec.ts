import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { overviewStore } from '@/stores/overviewStore'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { playableStore } from '@/stores/playableStore'

describe('overviewStore', () => {
  const h = createHarness({
    beforeEach: () => {
      overviewStore.state = {
        recentlyPlayed: [],
        recentlyAddedSongs: [],
        recentlyAddedAlbums: [],
        mostPlayedSongs: [],
        mostPlayedAlbums: [],
        mostPlayedArtists: [],
      }
    },
  })

  it('initializes the store', async () => {
    const songSyncMock = h.mock(playableStore, 'syncWithVault')
    const albumSyncMock = h.mock(albumStore, 'syncWithVault')
    const artistSyncMock = h.mock(artistStore, 'syncWithVault')
    const refreshMock = h.mock(overviewStore, 'refreshPlayStats')

    const mostPlayedSongs = h.factory('song', 7)
    const mostPlayedAlbums = h.factory('album', 6)
    const mostPlayedArtists = h.factory('artist', 6)
    const recentlyAddedSongs = h.factory('song', 9)
    const recentlyAddedAlbums = h.factory('album', 6)
    const recentlyPlayedSongs = h.factory('song', 9)

    const getMock = h.mock(http, 'get').mockResolvedValueOnce({
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
    const mostPlayedSongs = h.factory('song', 7)
    const recentlyPlayedSongs = h.factory('song', 9)

    const mostPlayedSongsMock = h.mock(playableStore, 'getMostPlayedSongs', mostPlayedSongs)
    recentlyPlayedStore.excerptState.playables = recentlyPlayedSongs

    overviewStore.refreshPlayStats()

    expect(mostPlayedSongsMock).toHaveBeenCalled()

    expect(overviewStore.state.recentlyPlayed).toEqual(recentlyPlayedSongs)
    expect(overviewStore.state.mostPlayedSongs).toEqual(mostPlayedSongs)
  })
})
