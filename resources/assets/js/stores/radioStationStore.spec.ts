import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { authService } from '@/services/authService'
import { http } from '@/services/http'
import { radioStationStore as store } from '@/stores/radioStationStore'

describe('radioStationStore', () => {
  const h = createHarness({
    beforeEach: () => {
      store.state.stations = []
    },
  })

  it('gets a station by ID', () => {
    store.state.stations = h.factory('radio-station', 3)
    const station = store.byId(store.state.stations[1].id)
    expect(station).toEqual(store.state.stations[1])
  })

  it('syncs stations', () => {
    const stations = h.factory('radio-station', 3)
    const synced = store.sync(stations)

    expect(synced).toHaveLength(3)
    expect(store.state.stations).toHaveLength(3)
    expect(store.state.stations[0]).toEqual(synced[0])

    const updatedStation = h.factory('radio-station', { id: stations[0].id, name: 'Updated Station' })
    const updatedSynced = store.sync(updatedStation)
    expect(updatedSynced).toHaveLength(1)
    expect(store.state.stations).toHaveLength(3)
    expect(store.state.stations[0].name).toBe('Updated Station')
  })

  it('gets source URL', () => {
    commonStore.state.cdn_url = 'http://test/'
    const station = h.factory('radio-station')
    h.mock(authService, 'getAudioToken', 'hadouken')

    expect(store.getSourceUrl(station)).toBe(`http://test/radio/stream/${station.id}?t=hadouken`)
  })

  it('gets the currently playing station', () => {
    store.state.stations = h.factory('radio-station', 3)
    expect(store.current).toBeNull()

    store.state.stations[1].playback_state = 'Playing'
    expect(store.current).toEqual(store.state.stations[1])

    store.state.stations[1].playback_state = 'Stopped'
    expect(store.current).toBeNull()
  })

  it('stores a new station', async () => {
    const createdStation = h.factory('radio-station')
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
    const fetchedStations = h.factory('radio-station', 5)
    const getMock = h.mock(http, 'get').mockResolvedValue(fetchedStations)
    const syncMock = h.mock(store, 'sync').mockReturnValue(fetchedStations)

    const stations = await store.fetchAll(favoritesOnly)

    expect(getMock).toHaveBeenCalledWith(`radio/stations?favorites_only=${favoritesOnly ? 'true' : 'false'}`)
    expect(syncMock).toHaveBeenCalledWith(fetchedStations)
    expect(stations).toEqual(fetchedStations)
  })

  it('updates a station', async () => {
    const station = h.factory('radio-station')

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

  it('deletes a station', async () => {
    const station = h.factory('radio-station')
    store.state.stations.push(station)

    const deleteMock = h.mock(http, 'delete').mockResolvedValue(null)

    await store.delete(station)

    expect(deleteMock).toHaveBeenCalledWith(`radio/stations/${station.id}`)
    expect(store.state.stations).toHaveLength(0)
  })

  it('toggles favorite status of a station', async () => {
    const station = h.factory('radio-station', { favorite: false })
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

  it('removes the logo from a station', async () => {
    const station = h.factory('radio-station', { logo: '/cool-logo.webp' })
    store.state.stations.push(station)
    const deleteMock = h.mock(http, 'delete').mockResolvedValue(null)

    await store.removeLogo(station)

    expect(deleteMock).toHaveBeenCalledWith(`radio/stations/${station.id}/logo`)
    expect(station.logo).toBeNull()
  })
})
