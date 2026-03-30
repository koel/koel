import { describe, expect, it } from 'vite-plus/test'
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
        recentlyAddedArtists: [],
        mostPlayedSongs: [],
        mostPlayedAlbums: [],
        mostPlayedArtists: [],
        leastPlayedSongs: [],
        randomSongs: [],
        similarSongs: [],
      }
    },
  })

  it('initializes the store', async () => {
    const songSyncMock = h.mock(playableStore, 'syncWithVault')
    const albumSyncMock = h.mock(albumStore, 'syncWithVault')
    const artistSyncMock = h.mock(artistStore, 'syncWithVault')
    const refreshMock = h.mock(overviewStore, 'refreshPlayStats')

    const mostPlayedSongs = h.factory('song', 6)
    const mostPlayedAlbums = h.factory('album', 6)
    const mostPlayedArtists = h.factory('artist', 6)
    const recentlyAddedSongs = h.factory('song', 6)
    const recentlyAddedAlbums = h.factory('album', 6)
    const recentlyAddedArtists = h.factory('artist', 6)
    const recentlyPlayedSongs = h.factory('song', 6)
    const leastPlayedSongs = h.factory('song', 6)
    const randomSongs = h.factory('song', 6)
    const similarSongs = h.factory('song', 6)

    const getMock = h.mock(http, 'get').mockResolvedValueOnce({
      most_played_songs: mostPlayedSongs,
      most_played_albums: mostPlayedAlbums,
      most_played_artists: mostPlayedArtists,
      recently_added_songs: recentlyAddedSongs,
      recently_added_albums: recentlyAddedAlbums,
      recently_added_artists: recentlyAddedArtists,
      recently_played_songs: recentlyPlayedSongs,
      least_played_songs: leastPlayedSongs,
      random_songs: randomSongs,
      similar_songs: similarSongs,
    })

    await overviewStore.fetch()

    expect(getMock).toHaveBeenCalledWith('overview')
    expect(songSyncMock).toHaveBeenNthCalledWith(1, mostPlayedSongs)
    expect(songSyncMock).toHaveBeenNthCalledWith(2, recentlyAddedSongs)
    expect(songSyncMock).toHaveBeenNthCalledWith(3, leastPlayedSongs)
    expect(songSyncMock).toHaveBeenNthCalledWith(4, randomSongs)
    expect(songSyncMock).toHaveBeenNthCalledWith(5, similarSongs)
    expect(songSyncMock).toHaveBeenNthCalledWith(6, recentlyPlayedSongs)
    expect(albumSyncMock).toHaveBeenNthCalledWith(1, mostPlayedAlbums)
    expect(albumSyncMock).toHaveBeenNthCalledWith(2, recentlyAddedAlbums)
    expect(artistSyncMock).toHaveBeenNthCalledWith(1, mostPlayedArtists)
    expect(artistSyncMock).toHaveBeenNthCalledWith(2, recentlyAddedArtists)
    expect(refreshMock).toHaveBeenCalled()
  })

  it('refreshes the store', () => {
    const mostPlayedSongs = h.factory('song', 6)
    const recentlyPlayedSongs = h.factory('song', 6)

    const mostPlayedSongsMock = h.mock(playableStore, 'getMostPlayedSongs', mostPlayedSongs)
    recentlyPlayedStore.excerptState.playables = recentlyPlayedSongs

    overviewStore.refreshPlayStats()

    expect(mostPlayedSongsMock).toHaveBeenCalled()

    expect(overviewStore.state.recentlyPlayed).toEqual(recentlyPlayedSongs)
    expect(overviewStore.state.mostPlayedSongs).toEqual(mostPlayedSongs)
  })
})
