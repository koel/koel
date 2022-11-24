import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils'
import factory from '@/__tests__/factory'
import { fireEvent } from '@testing-library/vue'
import PlaylistContextMenu from './PlaylistContextMenu.vue'

new class extends UnitTestCase {
  private async renderComponent (playlist: Playlist) {
    const rendered = await this.render(PlaylistContextMenu)
    eventBus.emit('PLAYLIST_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 }, playlist)
    await this.tick(2)
    return rendered
  }

  protected test () {
    it('edits a standard playlist', async () => {
      const playlist = factory<Playlist>('playlist')
      const { getByText } = await this.renderComponent(playlist)
      const emitMock = this.mock(eventBus, 'emit')

      await fireEvent.click(getByText('Edit'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist)
    })

    it('edits a smart playlist', async () => {
      const playlist = factory.states('smart')<Playlist>('playlist')
      const { getByText } = await this.renderComponent(playlist)
      const emitMock = this.mock(eventBus, 'emit')

      await fireEvent.click(getByText('Edit'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist)
    })

    it('deletes a playlist', async () => {
      const playlist = factory<Playlist>('playlist')
      const { getByText } = await this.renderComponent(playlist)
      const emitMock = this.mock(eventBus, 'emit')

      await fireEvent.click(getByText('Delete'))

      expect(emitMock).toHaveBeenCalledWith('PLAYLIST_DELETE', playlist)
    })
  }
}
