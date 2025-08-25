import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { playbackService } from '@/services/RadioPlaybackService'
import { resourcePermissionService } from '@/services/resourcePermissionService'
import { radioStationStore } from '@/stores/radioStationStore'
import Component from './RadioStationContextMenu.vue'

describe('radioStationContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async (station?: RadioStation, manageable = true) => {
    h.mock(resourcePermissionService, 'check').mockReturnValue(manageable)

    station = station || h.factory('radio-station', {
      favorite: false,
    })

    const rendered = h.render(Component)
    eventBus.emit('RADIO_STATION_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, station)

    // For all menu items (including Delete and Edit, which require permission checks) to be rendered
    await h.tick(7)

    return {
      ...rendered,
      station,
    }
  }

  it('renders with Edit/Delete items', async () => {
    await renderComponent()

    screen.getByText('Edit…')
    screen.getByText('Delete')
    screen.getByText('Play')
    screen.getByText('Favorite')
  })

  it('renders without Edit/Delete items', async () => {
    await renderComponent(undefined, false)

    expect(screen.queryByText('Edit…')).toBeNull()
    expect(screen.queryByText('Delete')).toBeNull()
    screen.getByText('Play')
    screen.getByText('Favorite')
  })

  it('plays', async () => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'play')

    const { station } = await renderComponent()
    await h.user.click(screen.getByText('Play'))

    expect(playMock).toHaveBeenCalledWith(station)
  })

  it('stops', async () => {
    h.createAudioPlayer()

    const stopMock = h.mock(playbackService, 'stop')

    await renderComponent(h.factory('radio-station', { playback_state: 'Playing' }))
    await h.user.click(screen.getByText('Stop'))

    expect(stopMock).toHaveBeenCalled()
  })

  it('favorites', async () => {
    const toggleMock = h.mock(radioStationStore, 'toggleFavorite')
    const { station } = await renderComponent(h.factory('radio-station', { favorite: false }))

    await h.user.click(screen.getByText('Favorite'))
    expect(toggleMock).toHaveBeenCalledWith(station)
  })

  it('undoes favorite', async () => {
    const toggleMock = h.mock(radioStationStore, 'toggleFavorite')
    const { station } = await renderComponent(h.factory('radio-station', { favorite: true }))

    await h.user.click(screen.getByText('Undo Favorite'))
    expect(toggleMock).toHaveBeenCalledWith(station)
  })

  it('requests edit form', async () => {
    const { station } = await renderComponent()

    const emitMock = h.mock(eventBus, 'emit')
    await h.user.click(screen.getByText('Edit…'))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_RADIO_STATION_FORM', station)
  })

  it('deletes', async () => {
    const deleteMock = h.mock(radioStationStore, 'delete')
    const { station } = await renderComponent()

    await h.user.click(screen.getByText('Delete'))
    expect(deleteMock).toHaveBeenCalledWith(station)
  })
})
