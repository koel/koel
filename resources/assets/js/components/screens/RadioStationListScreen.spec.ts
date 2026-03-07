import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vitest'
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
    },
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
    await waitFor(() => expect(screen.getByTestId('radio-station-grid').classList.contains(`as-thumbnails`)).toBe(true))
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
})
