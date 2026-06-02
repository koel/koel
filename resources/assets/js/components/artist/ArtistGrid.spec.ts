import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ArtistGrid.vue'

const virtualGridStub = {
  template: '<div><slot v-for="(item, i) in items" :key="i" :item="item" /></div>',
  props: ['items', 'minItemWidth'],
  methods: { scrollToTop() {} },
}

const artistCardStub = {
  template: '<div data-testid="artist-card" />',
  props: ['artist'],
}

describe('artistGrid.vue', () => {
  const h = createHarness()

  const renderComponent = (count = 3) => {
    const artists = h.factory('artist').make(count)
    return {
      artists,
      ...h.render(Component, {
        props: { artists },
        global: {
          stubs: {
            VirtualGridScroller: virtualGridStub,
            ArtistCard: artistCardStub,
          },
        },
      }),
    }
  }

  it('renders one ArtistCard per artist', () => {
    renderComponent(4)
    expect(screen.getAllByTestId('artist-card')).toHaveLength(4)
  })

  it('forwards scrolled-to-end from the underlying scroller', async () => {
    const { emitted } = renderComponent(1)

    screen.getByTestId('artist-grid').dispatchEvent(new CustomEvent('scrolled-to-end'))

    expect(emitted('scrolled-to-end')).toBeTruthy()
  })
})
