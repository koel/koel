import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { radioStationStore } from '@/stores/radioStationStore'
import { eventBus } from '@/utils/eventBus'
import Component from './RadioStationListScreen.vue'

describe('radioStationListScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => h.mock(radioStationStore, 'fetchAll'),
  })

  const renderComponent = async (stations?: RadioStation[]) => {
    radioStationStore.state.stations = stations || h.factory('radio-station', 9)

    const rendered = h.render(Component, {
      global: {
        stubs: {
          RadioStationCard: h.stub('radio-station-card'),
        },
      },
    })

    h.visit('/radio-stations')
    await h.tick()

    return rendered
  }

  it('renders', async () => {
    await renderComponent()
    expect(screen.getAllByTestId('radio-station-card')).toHaveLength(9)
  })

  it('shows a message when there is no station', async () => {
    await renderComponent([])

    await waitFor(() => screen.getByTestId('screen-empty-state'))
  })

  it.each<[ViewMode]>([['list'], ['thumbnails']])('sets layout from preferences', async mode => {
    preferences.temporary.radio_stations_view_mode = mode

    await renderComponent()

    await waitFor(() => expect(screen.getByTestId('radio-station-grid').classList.contains(`as-${mode}`)).toBe(true))
  })

  it('switches layout', async () => {
    await renderComponent()
    await h.tick()

    await h.user.click(screen.getByRole('radio', { name: 'View as list' }))
    await waitFor(() => expect(screen.getByTestId('radio-station-grid').classList.contains(`as-list`)).toBe(true))

    await h.user.click(screen.getByRole('radio', { name: 'View as thumbnails' }))
    await waitFor(() => expect(
      screen.getByTestId('radio-station-grid').classList.contains(`as-thumbnails`),
    ).toBe(true),
    )
  })

  it('requests the Add Radio Station form', async () => {
    const emitMock = h.mock(eventBus, 'emit')
    await renderComponent()
    await h.tick()

    const addButton = screen.getByRole('button', { name: 'Add a new station' })
    await h.user.click(addButton)

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_ADD_RADIO_STATION_FORM')
  })

  it('does not show the Add button in demo mode', async () => await h.withDemoMode(async () => {
    await renderComponent()
    await h.tick()

    expect(screen.queryByRole('button', { name: 'Add a new station' })).toBeNull()
  }))

  it('shows the Add button in demo mode for admins', async () => await h.withDemoMode(async () => {
    h.actingAsAdmin()
    await renderComponent()
    await h.tick()

    screen.getByRole('button', { name: 'Add a new station' })
  }))
})
