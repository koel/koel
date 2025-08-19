import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils/eventBus'
import { playbackService } from '@/services/RadioPlaybackService'
import { resourcePermissionService } from '@/services/resourcePermissionService'
import { radioStationStore } from '@/stores/radioStationStore'
import Component from './RadioStationContextMenu.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders with Edit/Delete items', async () => {
      await this.renderComponent()

      screen.getByText('Edit…')
      screen.getByText('Delete')
      screen.getByText('Play')
      screen.getByText('Favorite')
    })

    it('renders without Edit/Delete items', async () => {
      await this.renderComponent(undefined, false)

      expect(screen.queryByText('Edit…')).toBeNull()
      expect(screen.queryByText('Delete')).toBeNull()
      screen.getByText('Play')
      screen.getByText('Favorite')
    })

    it('plays', async () => {
      this.createAudioPlayer()

      const playMock = this.mock(playbackService, 'play')

      const { station } = await this.renderComponent()
      await this.user.click(screen.getByText('Play'))

      expect(playMock).toHaveBeenCalledWith(station)
    })

    it('stops', async () => {
      this.createAudioPlayer()

      const stopMock = this.mock(playbackService, 'stop')

      await this.renderComponent(factory('radio-station', { playback_state: 'Playing' }))
      await this.user.click(screen.getByText('Stop'))

      expect(stopMock).toHaveBeenCalled()
    })

    it('favorites', async () => {
      const toggleMock = this.mock(radioStationStore, 'toggleFavorite')
      const { station } = await this.renderComponent(factory('radio-station', { favorite: false }))

      await this.user.click(screen.getByText('Favorite'))
      expect(toggleMock).toHaveBeenCalledWith(station)
    })

    it('undoes favorite', async () => {
      const toggleMock = this.mock(radioStationStore, 'toggleFavorite')
      const { station } = await this.renderComponent(factory('radio-station', { favorite: true }))

      await this.user.click(screen.getByText('Undo Favorite'))
      expect(toggleMock).toHaveBeenCalledWith(station)
    })

    it('requests edit form', async () => {
      const { station } = await this.renderComponent()

      const emitMock = this.mock(eventBus, 'emit')
      await this.user.click(screen.getByText('Edit…'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_RADIO_STATION_FORM', station)
    })

    it('deletes', async () => {
      const deleteMock = this.mock(radioStationStore, 'delete')
      const { station } = await this.renderComponent()

      await this.user.click(screen.getByText('Delete'))
      expect(deleteMock).toHaveBeenCalledWith(station)
    })
  }

  private async renderComponent (station?: RadioStation, manageable = true) {
    this.mock(resourcePermissionService, 'check').mockReturnValue(manageable)

    station = station || factory('radio-station', {
      favorite: false,
    })

    const rendered = this.render(Component)
    eventBus.emit('RADIO_STATION_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, station)

    // For all menu items (including Delete and Edit, which require permission checks) to be rendered
    await this.tick(7)

    return {
      ...rendered,
      station,
    }
  }
}
