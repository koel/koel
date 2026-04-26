import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenModal } from '@/__tests__/assertions'
import { playbackService } from '@/services/RadioPlaybackService'
import { radioStationStore } from '@/stores/radioStationStore'
import EditRadioStationForm from '@/components/radio/EditRadioStationForm.vue'

const openModalMock = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({
    openModal: openModalMock,
  }),
}))

import Component from './RadioStationContextMenu.vue'

describe('radioStationContextMenu.vue', () => {
  const h = createHarness({
    beforeEach: () => openModalMock.mockClear(),
  })

  const renderComponent = async (station?: RadioStation, manageable = true) => {
    station =
      station ||
      h.factory('radio-station', {
        favorite: false,
        permissions: { edit: manageable, delete: manageable },
      })

    const rendered = h.render(Component, {
      props: {
        station,
      },
    })

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

  it('hides Edit when only delete is permitted', async () => {
    await renderComponent(h.factory('radio-station', { permissions: { edit: false, delete: true } }))

    expect(screen.queryByText('Edit…')).toBeNull()
    screen.getByText('Delete')
  })

  it('hides Delete when only edit is permitted', async () => {
    await renderComponent(h.factory('radio-station', { permissions: { edit: true, delete: false } }))

    expect(screen.queryByText('Delete')).toBeNull()
    screen.getByText('Edit…')
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

    await h.user.click(screen.getByText('Edit…'))

    await assertOpenModal(openModalMock, EditRadioStationForm, { station })
  })

  it('deletes', async () => {
    const deleteMock = h.mock(radioStationStore, 'delete')
    const { station } = await renderComponent()

    await h.user.click(screen.getByText('Delete'))
    expect(deleteMock).toHaveBeenCalledWith(station)
  })
})
