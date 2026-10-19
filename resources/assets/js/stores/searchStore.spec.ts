import { describe, expect, it } from 'vite-plus/test'
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
        keywords: string
      }>({
        excerpt: {
          playables: [],
          albums: [],
          artists: [],
          podcasts: [],
          radio_stations: [],
        },
        playables: [],
        keywords: '',
      })
    },
  })

  it('performs an excerpt search', async () => {
    const result: ExcerptSearchResult = {
      songs: h.factory('song').make(3),
      albums: h.factory('album').make(3),
      artists: h.factory('artist').make(3),
      podcasts: h.factory('podcast').make(3),
      radio_stations: h.factory('radio-station').make(3),
    }

    const getMock = h.mock(http, 'get').mockResolvedValue(result)
    const syncSongsMock = h.mock(playableStore, 'syncWithVault', result.songs)
    const syncAlbumsMock = h.mock(albumStore, 'syncWithVault', result.albums)
    const syncArtistsMock = h.mock(artistStore, 'syncWithVault', result.artists)

    await searchStore.excerptSearch('R&B #1 + more')

    expect(getMock).toHaveBeenCalledWith('search?q=R%26B+%231+%2B+more')
    expect(syncSongsMock).toHaveBeenCalledWith(result.songs)
    expect(syncAlbumsMock).toHaveBeenCalledWith(result.albums)
    expect(syncArtistsMock).toHaveBeenCalledWith(result.artists)

    expect(searchStore.state.excerpt.playables).toEqual(result.songs)
    expect(searchStore.state.excerpt.albums).toEqual(result.albums)
    expect(searchStore.state.excerpt.artists).toEqual(result.artists)
  })

  it('keeps the latest excerpt search results when an earlier search resolves last', async () => {
    const earlierResult: ExcerptSearchResult = {
      songs: h.factory('song').make(1),
      albums: h.factory('album').make(1),
      artists: h.factory('artist').make(1),
      podcasts: h.factory('podcast').make(1),
      radio_stations: h.factory('radio-station').make(1),
    }

    const latestResult: ExcerptSearchResult = {
      songs: h.factory('song').make(2),
      albums: h.factory('album').make(2),
      artists: h.factory('artist').make(2),
      podcasts: h.factory('podcast').make(2),
      radio_stations: h.factory('radio-station').make(2),
    }

    let resolveEarlierSearch: (result: ExcerptSearchResult) => void = () => {}

    h.mock(http, 'get')
      .mockReturnValueOnce(new Promise(resolve => (resolveEarlierSearch = resolve)))
      .mockResolvedValueOnce(latestResult)

    const earlierSearch = searchStore.excerptSearch('foo')
    await searchStore.excerptSearch('foobar')

    resolveEarlierSearch(earlierResult)
    await earlierSearch

    expect(searchStore.state.excerpt.playables).toEqual(latestResult.songs)
    expect(searchStore.state.excerpt.albums).toEqual(latestResult.albums)
    expect(searchStore.state.excerpt.artists).toEqual(latestResult.artists)
    expect(searchStore.state.excerpt.podcasts).toEqual(latestResult.podcasts)
    expect(searchStore.state.excerpt.radio_stations).toEqual(latestResult.radio_stations)
  })

  it('performs a song search', async () => {
    const songs = h.factory('song').make(3)

    const getMock = h.mock(http, 'get').mockResolvedValue(songs)
    const syncMock = h.mock(playableStore, 'syncWithVault', songs)

    await searchStore.playableSearch('R&B #1 + more')

    expect(getMock).toHaveBeenCalledWith('search/songs?q=R%26B+%231+%2B+more')
    expect(syncMock).toHaveBeenCalledWith(songs)

    expect(searchStore.state.playables).toEqual(songs)
  })

  it('resets the song result state', () => {
    searchStore.state.playables = h.factory('song').make(3)
    searchStore.resetPlayableResultState()
    expect(searchStore.state.playables).toEqual([])
  })
})
