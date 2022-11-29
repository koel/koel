import { expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import { eventBus } from '@/utils'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import PlaylistSidebarItem from './PlaylistSidebarItem.vue'

new class extends UnitTestCase {
  renderComponent (list: PlaylistLike) {
    this.render(PlaylistSidebarItem, {
      props: {
        list
      }
    })
  }

  protected test () {
    it('requests context menu if is playlist', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      const playlist = factory<Playlist>('playlist')
      this.renderComponent(playlist)

      await fireEvent.contextMenu(screen.getByRole('listitem'))

      expect(emitMock).toHaveBeenCalledWith('PLAYLIST_CONTEXT_MENU_REQUESTED', expect.anything(), playlist)
    })

    it.each<FavoriteList['name'] | RecentlyPlayedList['name']>(['Favorites', 'Recently Played'])
    ('does not request context menu if not playlist', async (name) => {
      const list: FavoriteList | RecentlyPlayedList = {
        name,
        songs: []
      }

      const emitMock = this.mock(eventBus, 'emit')
      this.renderComponent(list)

      await fireEvent.contextMenu(screen.getByRole('listitem'))

      expect(emitMock).not.toHaveBeenCalledWith('PLAYLIST_CONTEXT_MENU_REQUESTED', list)
    })
  }
}
