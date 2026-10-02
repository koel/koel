import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { authService } from '@/services/authService'
import { http } from '@/services/http'
import { radioStationStore as store } from '@/stores/radioStationStore'

describe('radioStationStore', () => {
  const h = createHarness({
    beforeEach: () => {
      store.state.stations = []
      store.nowPlaying.value = null
      store.stopPolling()
    },
  })

  it('gets a station by ID', () => {
    store.state.stations = h.factory('radio-station').make(3)
    const station = store.byId(store.state.stations[1].id)
    expect(station).toEqual(store.state.stations[1])
  })

  it('syncs stations', () => {
    const stations = h.factory('radio-station').make(3)
    const synced = store.sync(stations)

    expect(synced).toHaveLength(3)
    expect(store.state.stations).toHaveLength(3)
    expect(store.state.stations[0]).toEqual(synced[0])

    const updatedStation = h.factory('radio-station').make({ id: stations[0].id, name: 'Updated Station' })
    const updatedSynced = store.sync(updatedStation)
    expect(updatedSynced).toHaveLength(1)
    expect(store.state.stations).toHaveLength(3)
    expect(store.state.stations[0].name).toBe('Updated Station')
  })

  it('gets source URL', () => {
    commonStore.state.cdn_url = 'http://test/'
    const station = h.factory('radio-station').make()
    h.mock(authService, 'getAudioToken', 'hadouken')

    expect(store.getSourceUrl(station)).toBe(`http://test/radio/stream/${station.id}?t=hadouken`)
  })

  it('gets the currently playing station', () => {
    store.state.stations = h.factory('radio-station').make(3)
    expect(store.current).toBeNull()

    store.state.stations[1].playback_state = 'Playing'
    expect(store.current).toEqual(store.state.stations[1])

    store.state.stations[1].playback_state = 'Stopped'
    expect(store.current).toBeNull()
  })

  it('deletes a station', async () => {
    const station = h.factory('radio-station').make()
    store.state.stations.push(station)

    const deleteMock = h.mock(http, 'delete').mockResolvedValue(null)

    await store.delete(station)

    expect(deleteMock).toHaveBeenCalledWith(`radio/stations/${station.id}`)
    expect(store.state.stations).toHaveLength(0)
  })

  it('fetches now-playing metadata', async () => {
    const station = h.factory('radio-station').make()
    const getMock = h.mock(http, 'get').mockResolvedValue({
      stream_title: 'Artist - Song Title',
      updated_at: '2026-03-08T00:00:00.000Z',
    })

    await store.fetchNowPlaying(station)

    expect(getMock).toHaveBeenCalledWith(`radio/stations/${station.id}/now-playing`)
    expect(store.nowPlaying.value).toBe('Artist - Song Title')
  })

  it('starts and stops polling', () => {
    vi.useFakeTimers()

    const station = h.factory('radio-station').make()
    const fetchMock = h.mock(store, 'fetchNowPlaying').mockResolvedValue(undefined)

    store.startPolling(station)

    // Should fetch immediately
    expect(fetchMock).toHaveBeenCalledWith(station)
    expect(fetchMock).toHaveBeenCalledTimes(1)

    // Should fetch again after the poll interval
    vi.advanceTimersByTime(15_000)
    expect(fetchMock).toHaveBeenCalledTimes(2)

    store.stopPolling()

    // Should not fetch after stopping
    vi.advanceTimersByTime(15_000)
    expect(fetchMock).toHaveBeenCalledTimes(2)
    expect(store.nowPlaying.value).toBeNull()

    vi.useRealTimers()
  })

  it('toggles favorite status of a station', async () => {
    const station = h.factory('radio-station').make({ favorite: false })
    store.state.stations.push(station)

    const postMock = h.mock(http, 'post').mockResolvedValue({ id: station.id, type: 'radio-station' })

    await store.toggleFavorite(station)

    expect(postMock).toHaveBeenCalledWith('favorites/toggle', { type: 'radio-station', id: station.id })
    expect(station.favorite).toBe(true)

    // Toggle again to remove favorite
    h.mock(http, 'post').mockResolvedValue(null)
    await store.toggleFavorite(station)
    expect(station.favorite).toBe(false)
  })
})
