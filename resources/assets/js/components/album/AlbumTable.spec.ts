import { describe, expect, it, vi } from 'vite-plus/test'
import type { Mock } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import { useContextMenu } from '@/composables/useContextMenu'
import Router from '@/router'
import AlbumContextMenu from './AlbumContextMenu.vue'
import SelectedAlbumsContextMenu from './SelectedAlbumsContextMenu.vue'
import Component from './AlbumTable.vue'

const virtualScrollerStub = {
  name: 'VirtualScrollerStub',
  props: ['items', 'itemHeight', 'carriesScreenHeader'],
  template:
    '<div><slot name="before" /><template v-for="item in items" :key="item.item.id"><slot :item="item" /></template></div>',
}

vi.mock('@/composables/useContextMenu')

describe('albumTable.vue', () => {
  const h = createHarness()

  const renderWithAlbums = (count = 3) => {
    const albums = count === 0 ? [] : h.factory('album').make(count)
    return {
      albums,
      ...h.render(Component, {
        props: { albums, field: 'name' as const, order: 'asc' as const },
        global: { stubs: { VirtualScroller: virtualScrollerStub } },
      }),
    }
  }

  it('emits sort with toggled order when a header is clicked', async () => {
    const { emitted } = renderWithAlbums(0)

    await h.user.click(screen.getByRole('button', { name: 'Name' }))

    expect(emitted('sort')?.[0]).toEqual(['name', 'desc'])
  })

  it('opens the album menu when right-clicking a row', async () => {
    const { openContextMenu } = useContextMenu()
    const { albums } = renderWithAlbums()

    await h.trigger(screen.getAllByTestId('album-row')[1], 'contextMenu')

    await assertOpenContextMenu(openContextMenu as Mock, AlbumContextMenu, { album: albums[1] })
  })

  it('opens the menu for all selected rows when several are selected', async () => {
    const { openContextMenu } = useContextMenu()
    const { albums } = renderWithAlbums()
    const rows = screen.getAllByTestId('album-row')

    await h.trigger(rows[0], 'click')
    await h.trigger(rows[2], 'click', { shiftKey: true })
    await h.trigger(rows[1], 'contextMenu')

    await assertOpenContextMenu(openContextMenu as Mock, SelectedAlbumsContextMenu, { albums })
  })

  it('opens the selected album on Enter', async () => {
    const goMock = h.mock(Router, 'go')
    const { albums } = renderWithAlbums()

    const row = screen.getAllByTestId('album-row')[1]

    await h.trigger(row, 'click')
    await h.trigger(row, 'keyDown', { key: 'Enter' })

    expect(goMock).toHaveBeenCalledWith(expect.stringContaining('/albums/' + albums[1].id))
  })
})
