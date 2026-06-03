import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenModal } from '@/__tests__/assertions'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { radioStationStore } from '@/stores/radioStationStore'
import AddRadioStationForm from '@/components/radio/AddRadioStationForm.vue'

const openModalMock = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({
    openModal: openModalMock,
  }),
}))

import Component from './RadioStationListScreen.vue'

describe('radioStationListScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      openModalMock.mockClear()
      h.mock(radioStationStore, 'fetchAll')
      preferences.temporary.radio_stations_view_mode = 'grid'
    },
  })

  const renderComponent = async (stations?: RadioStation[]) => {
    radioStationStore.state.stations = stations || h.factory('radio-station').make(9)

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

  it('renders the grid by default', async () => {
    await renderComponent()

    await waitFor(() => screen.getByTestId('radio-station-grid'))
    expect(screen.queryByTestId('radio-station-table')).toBeNull()
  })

  it('renders the table when the view mode is table', async () => {
    preferences.temporary.radio_stations_view_mode = 'table'

    await renderComponent()

    await waitFor(() => screen.getByTestId('radio-station-table'))
    expect(screen.queryByTestId('radio-station-grid')).toBeNull()
  })

  it('switches between grid and table via the view mode toggle', async () => {
    await renderComponent()
    await h.tick()

    screen.getByTestId('radio-station-grid')
    expect(screen.queryByTestId('radio-station-table')).toBeNull()

    await h.user.click(screen.getByRole('radio', { name: 'View as table' }))
    await waitFor(() => {
      screen.getByTestId('radio-station-table')
      expect(screen.queryByTestId('radio-station-grid')).toBeNull()
    })

    await h.user.click(screen.getByRole('radio', { name: 'View as grid' }))
    await waitFor(() => {
      screen.getByTestId('radio-station-grid')
      expect(screen.queryByTestId('radio-station-table')).toBeNull()
    })
  })

  it('requests the Add Radio Station form', async () => {
    await renderComponent()
    await h.tick()

    const addButton = screen.getByRole('button', { name: 'Add a new station' })
    await h.user.click(addButton)

    await assertOpenModal(openModalMock, AddRadioStationForm)
  })

  it('does not show the Add button in demo mode', async () =>
    await h.withDemoMode(async () => {
      await renderComponent()
      await h.tick()

      expect(screen.queryByRole('button', { name: 'Add a new station' })).toBeNull()
    }))

  it('shows the Add button in demo mode for admins', async () =>
    await h.withDemoMode(async () => {
      h.actingAsAdmin()
      await renderComponent()
      await h.tick()

      screen.getByRole('button', { name: 'Add a new station' })
    }))

  it('shows all or only favorites upon toggling the button', async () => {
    await renderComponent([
      ...h.factory('radio-station').make({ favorite: true }, 3),
      ...h.factory('radio-station').make({ favorite: false }, 6),
    ])

    expect(screen.getAllByTestId('radio-station-card')).toHaveLength(9)

    await h.user.click(screen.getByRole('button', { name: 'Show favorites only' }))
    await waitFor(() => expect(screen.getAllByTestId('radio-station-card')).toHaveLength(3))

    await h.user.click(screen.getByRole('button', { name: 'Show all' }))
    await waitFor(() => expect(screen.getAllByTestId('radio-station-card')).toHaveLength(9))
  })

  it('shows contextual empty state when no favorite stations', async () => {
    await renderComponent(h.factory('radio-station').make({ favorite: false }, 3))

    await h.user.click(screen.getByRole('button', { name: 'Show favorites only' }))

    await waitFor(() => {
      const emptyState = screen.getByTestId('screen-empty-state')
      expect(emptyState.textContent).toContain('No favorite stations')
    })
  })
})
