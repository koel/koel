import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/RadioPlaybackService'
import { radioStationStore } from '@/stores/radioStationStore'

import Component from './RadioStationContextMenu.vue'

describe('radioStationContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async (station?: RadioStation, manageable = true) => {
    station =
      station ||
      h.factory('radio-station').make({
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

  it('renders without Edit/Delete items', async () => {
    await renderComponent(undefined, false)

    expect(screen.queryByText('Edit…')).toBeNull()
    expect(screen.queryByText('Delete')).toBeNull()
    screen.getByText('Play')
    screen.getByText('Favorite')
  })

  it('hides Edit when only delete is permitted', async () => {
    await renderComponent(h.factory('radio-station').make({ permissions: { edit: false, delete: true } }))

    expect(screen.queryByText('Edit…')).toBeNull()
    screen.getByText('Delete')
  })

  it('hides Delete when only edit is permitted', async () => {
    await renderComponent(h.factory('radio-station').make({ permissions: { edit: true, delete: false } }))

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

    await renderComponent(h.factory('radio-station').make({ playback_state: 'Playing' }))
    await h.user.click(screen.getByText('Stop'))

    expect(stopMock).toHaveBeenCalled()
  })

  it('deletes', async () => {
    const deleteMock = h.mock(radioStationStore, 'delete')
    const { station } = await renderComponent()

    await h.user.click(screen.getByText('Delete'))
    expect(deleteMock).toHaveBeenCalledWith(station)
  })
})
