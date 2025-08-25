import { describe, expect, it } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './PlaylistSidebarItem.vue'

describe('playlistSidebarItem.vue', () => {
  const h = createHarness()

  const renderComponent = (list: PlaylistLike) => {
    h.render(Component, {
      props: {
        list,
      },
    })
  }

  it('requests context menu if is playlist', async () => {
    const emitMock = h.mock(eventBus, 'emit')
    const playlist = h.factory('playlist')
    renderComponent(playlist)

    await fireEvent.contextMenu(screen.getByRole('listitem'))

    expect(emitMock).toHaveBeenCalledWith('PLAYLIST_CONTEXT_MENU_REQUESTED', expect.anything(), playlist)
  })

  it.each<FavoriteList['name'] | RecentlyPlayedList['name']>(['Favorites', 'Recently Played'])
  ('does not request context menu if not playlist', async name => { // eslint-disable-line no-unexpected-multiline
    const list: FavoriteList | RecentlyPlayedList = {
      name,
      playables: [],
    }

    const emitMock = h.mock(eventBus, 'emit')
    renderComponent(list)

    await fireEvent.contextMenu(screen.getByRole('listitem'))

    expect(emitMock).not.toHaveBeenCalledWith('PLAYLIST_CONTEXT_MENU_REQUESTED', list)
  })
})
