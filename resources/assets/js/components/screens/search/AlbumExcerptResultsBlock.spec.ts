import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AlbumExcerptResultsBlock.vue'

describe('albumExcerptResultsBlock.vue', () => {
  const h = createHarness()

  it('shows "None found." when no albums', () => {
    h.render(Component, {
      props: { albums: [], searching: false },
    })

    screen.getByText('None found.')
  })

  it('renders album cards when albums are provided', () => {
    const albums = h.factory('album', 3)

    h.render(Component, {
      props: { albums, searching: false },
    })

    expect(screen.queryByText('None found.')).toBeNull()
  })
})
