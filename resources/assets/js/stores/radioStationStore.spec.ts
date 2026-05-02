import { beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { authService } from '@/services/authService'
import { http } from '@/services/http'
import { radioStationStore as store } from '@/stores/radioStationStore'
import { playbackService as radioPlaybackService } from '@/services/RadioPlaybackService'

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

  it('stores a new station', async () => {
    const createdStation = h.factory('radio-station').make()
    const postMock = h.mock(http, 'post').mockResolvedValue(createdStation)
    const syncMock = h.mock(store, 'sync').mockReturnValue([createdStation])

    const data = {
      name: 'Test Station',
      url: 'https://test.com/stream',
      logo: 'data:image/png;base64,whatever',
      description: 'A test radio station',
      is_public: true,
    }

    await store.store(data)

    expect(postMock).toHaveBeenCalledWith('radio/stations', data)
    expect(syncMock).toHaveBeenCalledWith(createdStation)
  })

  it.each([[true], [false]])('fetches all stations with favorites only set to %s', async favoritesOnly => {
    const fetchedStations = h.factory('radio-station').make(5)
    const getMock = h.mock(http, 'get').mockResolvedValue(fetchedStations)
    const syncMock = h.mock(store, 'sync').mockReturnValue(fetchedStations)

    const stations = await store.fetchAll(favoritesOnly)

    expect(getMock).toHaveBeenCalledWith(`radio/stations?favorites_only=${favoritesOnly ? 'true' : 'false'}`)
    expect(syncMock).toHaveBeenCalledWith(fetchedStations)
    expect(stations).toEqual(fetchedStations)
  })

  it('updates a station', async () => {
    const station = h.factory('radio-station').make()

    const updatedData = {
      url: 'https://test.com/new-stream',
      logo: 'data:image/png;base64,updatedLogo',
      description: 'Updated description',
      is_public: false,
      name: 'Updated Station',
    }

    const putMock = h.mock(http, 'put').mockResolvedValue({ ...station, ...updatedData })
    const syncMock = h.mock(store, 'sync').mockReturnValue([{ ...station, ...updatedData }])

    const updatedStation = await store.update(station, updatedData)

    expect(putMock).toHaveBeenCalledWith(`radio/stations/${station.id}`, updatedData)
    expect(syncMock).toHaveBeenCalledWith({ ...station, ...updatedData })
    expect(updatedStation.name).toBe('Updated Station')
  })

  describe('live-edit playback sync', () => {
    // The store calls playback('radio') on URL change, which activates
    // the radio playback service against #audio-player. Stand the
    // element up so activation doesn't blow up on this.media.volume.
    beforeEach(() => h.createAudioPlayer())

    const updateOnAirStation = async (overrides: Partial<RadioStation>) => {
      const station = h.factory('radio-station').make({
        url: 'https://old.example.com/stream',
        playback_state: 'Playing',
        ...overrides,
      })
      store.state.stations.push(station)

      const newData = {
        name: station.name,
        url: 'https://new.example.com/stream',
        description: station.description,
        is_public: station.is_public,
      }
      const updated = { ...station, ...newData }

      h.mock(http, 'put').mockResolvedValue(updated)
      h.mock(store, 'sync').mockImplementation((s: any) => {
        Object.assign(station, s)
        return [station as any]
      })

      const playMock = h.mock(radioPlaybackService, 'play').mockResolvedValue(undefined)

      await store.update(station, newData)

      return { station, playMock }
    }

    it('restarts playback when the on-air station has its URL changed', async () => {
      const { station, playMock } = await updateOnAirStation({})

      expect(playMock).toHaveBeenCalledTimes(1)
      expect(playMock).toHaveBeenCalledWith(station)
    })

    it('does not restart playback when only the name changed', async () => {
      const station = h.factory('radio-station').make({
        url: 'https://example.com/stream',
        playback_state: 'Playing',
      })
      store.state.stations.push(station)

      const newData = {
        name: 'A Brand New Name',
        url: station.url,
        description: station.description,
        is_public: station.is_public,
      }
      h.mock(http, 'put').mockResolvedValue({ ...station, ...newData })
      h.mock(store, 'sync').mockImplementation((s: any) => {
        Object.assign(station, s)
        return [station as any]
      })
      const playMock = h.mock(radioPlaybackService, 'play').mockResolvedValue(undefined)

      await store.update(station, newData)

      expect(playMock).not.toHaveBeenCalled()
    })

    it('does not restart playback when the on-air station is paused', async () => {
      const { playMock } = await updateOnAirStation({ playback_state: 'Paused' })
      expect(playMock).not.toHaveBeenCalled()
    })

    it('does not restart playback when the edited station is not on air', async () => {
      const onAir = h.factory('radio-station').make({ playback_state: 'Playing' })
      const other = h.factory('radio-station').make({
        url: 'https://old.example.com/stream',
        playback_state: 'Stopped',
      })
      store.state.stations.push(onAir, other)

      const newData = {
        name: other.name,
        url: 'https://new.example.com/stream',
        description: other.description,
        is_public: other.is_public,
      }
      h.mock(http, 'put').mockResolvedValue({ ...other, ...newData })
      h.mock(store, 'sync').mockImplementation((s: any) => {
        Object.assign(other, s)
        return [other as any]
      })
      const playMock = h.mock(radioPlaybackService, 'play').mockResolvedValue(undefined)

      await store.update(other, newData)

      expect(playMock).not.toHaveBeenCalled()
    })
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
