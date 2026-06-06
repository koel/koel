import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AlbumGrid.vue'

const virtualGridStub = {
  template: '<div><slot v-for="(item, i) in items" :key="i" :item="item" /></div>',
  props: ['items', 'minItemWidth'],
  methods: { scrollToTop() {} },
}

const albumCardStub = {
  template: '<div data-testid="album-card" />',
  props: ['album', 'showReleaseYear'],
}

describe('albumGrid.vue', () => {
  const h = createHarness()

  const renderComponent = (count = 3, showReleaseYear = false) => {
    const albums = h.factory('album').make(count)
    return {
      albums,
      ...h.render(Component, {
        props: { albums, showReleaseYear },
        global: {
          stubs: {
            VirtualGridScroller: virtualGridStub,
            AlbumCard: albumCardStub,
          },
        },
      }),
    }
  }

  it('renders one AlbumCard per album', () => {
    renderComponent(5)
    expect(screen.getAllByTestId('album-card')).toHaveLength(5)
  })

  it('forwards scrolled-to-end from the underlying scroller', async () => {
    const { emitted } = renderComponent(1)

    screen.getByTestId('album-grid').dispatchEvent(new CustomEvent('scrolled-to-end'))

    expect(emitted('scrolled-to-end')).toBeTruthy()
  })
})
