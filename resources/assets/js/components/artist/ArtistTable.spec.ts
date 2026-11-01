import { describe, expect, it, vi } from 'vite-plus/test'
import type { Mock } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import { useContextMenu } from '@/composables/useContextMenu'
import Router from '@/router'
import ArtistContextMenu from './ArtistContextMenu.vue'
import SelectedArtistsContextMenu from './SelectedArtistsContextMenu.vue'
import Component from './ArtistTable.vue'

const virtualScrollerStub = {
  name: 'VirtualScrollerStub',
  props: ['items', 'itemHeight', 'carriesScreenHeader'],
  template:
    '<div><slot name="before" /><template v-for="item in items" :key="item.item.id"><slot :item="item" /></template></div>',
}

vi.mock('@/composables/useContextMenu')

describe('artistTable.vue', () => {
  const h = createHarness()

  const renderWithArtists = (count = 3) => {
    const artists = count === 0 ? [] : h.factory('artist').make(count)
    return {
      artists,
      ...h.render(Component, {
        props: { artists, field: 'name' as const, order: 'asc' as const },
        global: { stubs: { VirtualScroller: virtualScrollerStub } },
      }),
    }
  }

  it('emits sort with toggled order when a header is clicked', async () => {
    const { emitted } = renderWithArtists(0)

    await h.user.click(screen.getByRole('button', { name: 'Name' }))

    expect(emitted('sort')?.[0]).toEqual(['name', 'desc'])
  })

  it('opens the artist menu when right-clicking a row', async () => {
    const { openContextMenu } = useContextMenu()
    const { artists } = renderWithArtists()

    await h.trigger(screen.getAllByTestId('artist-row')[1], 'contextMenu')

    await assertOpenContextMenu(openContextMenu as Mock, ArtistContextMenu, { artist: artists[1] })
  })

  it('opens the menu for all selected rows when several are selected', async () => {
    const { openContextMenu } = useContextMenu()
    const { artists } = renderWithArtists()
    const rows = screen.getAllByTestId('artist-row')

    await h.trigger(rows[0], 'click')
    await h.trigger(rows[2], 'click', { shiftKey: true })
    await h.trigger(rows[1], 'contextMenu')

    await assertOpenContextMenu(openContextMenu as Mock, SelectedArtistsContextMenu, { artists })
  })

  it('opens the selected artist on Enter', async () => {
    const goMock = h.mock(Router, 'go')
    const { artists } = renderWithArtists()

    const row = screen.getAllByTestId('artist-row')[1]

    await h.trigger(row, 'click')
    await h.trigger(row, 'keyDown', { key: 'Enter' })

    expect(goMock).toHaveBeenCalledWith(expect.stringContaining('/artists/' + artists[1].id))
  })
})
