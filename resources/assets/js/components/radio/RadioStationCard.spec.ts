import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { playbackService } from '@/services/RadioPlaybackService'
import { radioStationStore } from '@/stores/radioStationStore'
import { eventBus } from '@/utils/eventBus'
import Component from './RadioStationCard.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders with stopped state', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('renders with playing state', () => {
      const station = this.createRadioStation({ playback_state: 'Playing' })
      expect(this.renderComponent(station).html()).toMatchSnapshot()
    })

    it('plays', async () => {
      this.createAudioPlayer()

      const playMock = this.mock(playbackService, 'play')
      const { station } = this.renderComponent()

      await this.user.click(screen.getByTitle('Play/pause Beethoven Goes Metal'))

      expect(playMock).toHaveBeenCalledWith(station)
    })

    it('requests context menu', async () => {
      const { station } = this.renderComponent()
      const emitMock = this.mock(eventBus, 'emit')
      await this.trigger(screen.getByTestId('radio-station-card'), 'contextMenu')

      expect(emitMock).toHaveBeenCalledWith('RADIO_STATION_CONTEXT_MENU_REQUESTED', expect.any(MouseEvent), station)
    })

    it('if favorite, has a Favorite icon button that undoes favorite state', async () => {
      const album = this.createRadioStation({ favorite: true })
      const toggleMock = this.mock(radioStationStore, 'toggleFavorite')
      this.renderComponent(album)

      await this.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))

      expect(toggleMock).toHaveBeenCalledWith(album)
    })

    it('if not favorite, does not have a Favorite icon button', async () => {
      this.renderComponent()
      expect(screen.queryByRole('button', { name: 'Undo Favorite' })).toBeNull()
    })
  }

  private createRadioStation (overrides: Partial<RadioStation> = {}): RadioStation {
    return factory('radio-station', {
      id: '----',
      name: 'Beethoven Goes Metal',
      url: 'https://beet.stream/metal',
      logo: 'https://example.com/logo.jpg',
      description: 'Heavy af',
      is_public: false,
      favorite: false,
      ...overrides,
    })
  }

  private renderComponent (station?: RadioStation) {
    station = station || this.createRadioStation()

    const render = this.render(Component, {
      props: {
        station: station || this.createRadioStation(),
      },
    })

    return {
      ...render,
      station,
    }
  }
}
