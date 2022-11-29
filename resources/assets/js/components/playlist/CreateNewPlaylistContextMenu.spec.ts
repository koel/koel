import { expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils'
import { Events } from '@/config'
import CreateNewPlaylistContextMenu from './CreateNewPlaylistContextMenu.vue'

new class extends UnitTestCase {
  private async renderComponent () {
    await this.render(CreateNewPlaylistContextMenu)
    eventBus.emit('CREATE_NEW_PLAYLIST_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent)
    await this.tick(2)
  }

  protected test () {
    it.each<[string, keyof Events]>([
      ['playlist-context-menu-create-simple', 'MODAL_SHOW_CREATE_PLAYLIST_FORM'],
      ['playlist-context-menu-create-smart', 'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM'],
      ['playlist-context-menu-create-folder', 'MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM']
    ])('when clicking on %s, should emit %s', async (id, eventName) => {
      await this.renderComponent()
      const emitMock = this.mock(eventBus, 'emit')
      await this.user.click(screen.getByTestId(id))
      await waitFor(() => expect(emitMock).toHaveBeenCalledWith(eventName))
    })
  }
}
