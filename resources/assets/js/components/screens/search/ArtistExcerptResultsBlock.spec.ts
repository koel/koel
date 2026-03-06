import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ArtistExcerptResultsBlock.vue'

describe('artistExcerptResultsBlock.vue', () => {
  const h = createHarness()

  it('shows "None found." when no artists', () => {
    h.render(Component, {
      props: { artists: [], searching: false },
    })

    screen.getByText('None found.')
  })

  it('renders artist cards when artists are provided', () => {
    const artists = h.factory('artist', 3)

    h.render(Component, {
      props: { artists, searching: false },
    })

    expect(screen.queryByText('None found.')).toBeNull()
  })
})
