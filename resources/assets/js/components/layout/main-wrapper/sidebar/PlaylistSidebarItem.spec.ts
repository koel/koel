import type { Mock } from 'vitest'
import { describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useContextMenu } from '@/composables/useContextMenu'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import Component from './PlaylistSidebarItem.vue'
import PlaylistContextMenu from '@/components/playlist/PlaylistContextMenu.vue'

vi.mock('@/composables/useContextMenu')

describe('playlistSidebarItem.vue', () => {
  const h = createHarness()

  const renderComponent = (list?: PlaylistLike) => {
    list = list ?? h.factory('playlist')

    const rendered = h.render(Component, {
      props: {
        list,
      },
    })

    return {
      ...rendered,
      list,
    }
  }

  it('requests context menu if is playlist', async () => {
    const { openContextMenu } = useContextMenu()
    const { list } = renderComponent()

    await fireEvent.contextMenu(screen.getByRole('listitem'))
    await assertOpenContextMenu(openContextMenu as Mock, PlaylistContextMenu, { playlist: list })
  })

  it.each<FavoriteList['name'] | RecentlyPlayedList['name']>(['Favorites', 'Recently Played'])
  ('does not request context menu if not playlist', async name => { // eslint-disable-line no-unexpected-multiline
    const { openContextMenu } = useContextMenu()
    ;(openContextMenu as Mock).mockClear()
    const list: FavoriteList | RecentlyPlayedList = {
      name,
      playables: [],
    }

    renderComponent(list)

    await fireEvent.contextMenu(screen.getByRole('listitem'))

    expect(openContextMenu).not.toHaveBeenCalled()
  })
})
