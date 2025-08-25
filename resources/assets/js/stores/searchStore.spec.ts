import { describe, expect, it } from 'vitest'
import { reactive } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import type { ExcerptSearchResult, ExcerptState } from '@/stores/searchStore'
import { searchStore } from '@/stores/searchStore'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'

describe('searchStore', () => {
  const h = createHarness({
    beforeEach: () => {
      searchStore.state = reactive<{
        excerpt: ExcerptState
        playables: Playable[]
      }>({
        excerpt: {
          playables: [],
          albums: [],
          artists: [],
          podcasts: [],
          radio_stations: [],
        },
        playables: [],
      })
    },
  })

  it('performs an excerpt search', async () => {
    const result: ExcerptSearchResult = {
      songs: h.factory('song', 3),
      albums: h.factory('album', 3),
      artists: h.factory('artist', 3),
      podcasts: h.factory('podcast', 3),
      radio_stations: h.factory('radio-station', 3),
    }

    const getMock = h.mock(http, 'get').mockResolvedValue(result)
    const syncSongsMock = h.mock(playableStore, 'syncWithVault', result.songs)
    const syncAlbumsMock = h.mock(albumStore, 'syncWithVault', result.albums)
    const syncArtistsMock = h.mock(artistStore, 'syncWithVault', result.artists)

    await searchStore.excerptSearch('test')

    expect(getMock).toHaveBeenCalledWith('search?q=test')
    expect(syncSongsMock).toHaveBeenCalledWith(result.songs)
    expect(syncAlbumsMock).toHaveBeenCalledWith(result.albums)
    expect(syncArtistsMock).toHaveBeenCalledWith(result.artists)

    expect(searchStore.state.excerpt.playables).toEqual(result.songs)
    expect(searchStore.state.excerpt.albums).toEqual(result.albums)
    expect(searchStore.state.excerpt.artists).toEqual(result.artists)
  })

  it('performs a song search', async () => {
    const songs = h.factory('song', 3)

    const getMock = h.mock(http, 'get').mockResolvedValue(songs)
    const syncMock = h.mock(playableStore, 'syncWithVault', songs)

    await searchStore.playableSearch('test')

    expect(getMock).toHaveBeenCalledWith('search/songs?q=test')
    expect(syncMock).toHaveBeenCalledWith(songs)

    expect(searchStore.state.playables).toEqual(songs)
  })

  it('resets the song result state', () => {
    searchStore.state.playables = h.factory('song', 3)
    searchStore.resetPlayableResultState()
    expect(searchStore.state.playables).toEqual([])
  })
})
