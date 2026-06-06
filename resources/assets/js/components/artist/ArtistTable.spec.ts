import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ArtistTable.vue'

const virtualScrollerStub = {
  name: 'VirtualScrollerStub',
  props: ['items', 'itemHeight'],
  template: '<div><template v-for="item in items" :key="item.id"><slot :item="item" /></template></div>',
}

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

  it('renders a sort-by-name header', () => {
    renderWithArtists(3)

    screen.getByTitle('Sort by name')
  })

  it('emits sort with toggled order when a header is clicked', async () => {
    const { emitted } = renderWithArtists(0)

    await h.user.click(screen.getByTitle('Sort by name'))

    expect(emitted('sort')?.[0]).toEqual(['name', 'desc'])
  })

  it('shows a more-actions button in the header action menu', () => {
    renderWithArtists(0)

    screen.getByRole('button', { name: 'Sort' })
  })
})
