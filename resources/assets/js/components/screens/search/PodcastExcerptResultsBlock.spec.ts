import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PodcastExcerptResultsBlock.vue'

describe('podcastExcerptResultsBlock.vue', () => {
  const h = createHarness()

  it('shows "None found." when no podcasts', () => {
    h.render(Component, {
      props: { podcasts: [], searching: false },
    })

    screen.getByText('None found.')
  })

  it('renders podcast cards when podcasts are provided', () => {
    const podcasts = h.factory('podcast', 3)

    h.render(Component, {
      props: { podcasts, searching: false },
    })

    expect(screen.queryByText('None found.')).toBeNull()
  })
})
