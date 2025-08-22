import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { radioStationStore } from '@/stores/radioStationStore'
import { eventBus } from '@/utils/eventBus'
import Component from './RadioStationListScreen.vue'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => this.mock(radioStationStore, 'fetchAll'))
  }

  protected test () {
    it('renders', async () => {
      await this.renderComponent()
      expect(screen.getAllByTestId('radio-station-card')).toHaveLength(9)
    })

    it('shows a message when there is no station', async () => {
      await this.renderComponent([])

      await waitFor(() => screen.getByTestId('screen-empty-state'))
    })

    it.each<[ViewMode]>([['list'], ['thumbnails']])('sets layout from preferences', async mode => {
      preferences.temporary.radio_stations_view_mode = mode

      await this.renderComponent()

      await waitFor(() => expect(screen.getByTestId('radio-station-grid').classList.contains(`as-${mode}`)).toBe(true))
    })

    it('switches layout', async () => {
      await this.renderComponent()
      await this.tick()

      await this.user.click(screen.getByRole('radio', { name: 'View as list' }))
      await waitFor(() => expect(screen.getByTestId('radio-station-grid').classList.contains(`as-list`)).toBe(true))

      await this.user.click(screen.getByRole('radio', { name: 'View as thumbnails' }))
      await waitFor(() => expect(
        screen.getByTestId('radio-station-grid').classList.contains(`as-thumbnails`),
      ).toBe(true),
      )
    })

    it('requests the Add Radio Station form', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      await this.renderComponent()
      await this.tick()

      const addButton = screen.getByRole('button', { name: 'Add a new station' })
      await this.user.click(addButton)

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_ADD_RADIO_STATION_FORM')
    })

    it('does not show the Add button in demo mode', async () => {
      this.enableDemoMode()
      await this.renderComponent()
      await this.tick()

      expect(screen.queryByRole('button', { name: 'Add a new station' })).toBeNull()
    })

    it('shows the Add button in demo mode for admins', async () => {
      this.beAdmin().enableDemoMode()
      await this.renderComponent()
      await this.tick()

      screen.getByRole('button', { name: 'Add a new station' })
    })
  }

  private async renderComponent (stations?: RadioStation[]) {
    radioStationStore.state.stations = stations || factory('radio-station', 9)

    const rendered = this.render(Component, {
      global: {
        stubs: {
          RadioStationCard: this.stub('radio-station-card'),
        },
      },
    })

    await this.router.activateRoute({ path: 'radio/stations', screen: 'Radio.Stations' })

    return rendered
  }
}
