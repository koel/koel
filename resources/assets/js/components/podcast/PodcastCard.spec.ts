import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { podcastStore } from '@/stores/podcastStore'
import Component from './PodcastCard.vue'

describe('podcastCard.vue', () => {
  const h = createHarness()

  const renderComponent = (podcast?: Podcast) => {
    podcast = podcast || h.factory('podcast', {
      title: 'A Brief History of Time',
      author: 'Stephen Hawking',
      favorite: false,
    })

    const rendered = h.render(Component, {
      props: {
        podcast,
      },
      global: {
        stubs: {
          EpisodeProgress: h.stub('episode-progress-stub'),
        },
      },
    })

    return {
      rendered,
      podcast,
    }
  }

  it('renders', () => {
    const { podcast } = renderComponent()
    screen.getByText('A Brief History of Time')
    screen.getByText('Stephen Hawking')

    expect(screen.getByTestId('title').getAttribute('href')).toBe(`/#/podcasts/${podcast.id}`)

    expect(screen.queryByRole('button', { name: 'Undo Favorite' })).toBeNull()
    expect(screen.queryByRole('button', { name: 'Favorite' })).toBeNull()
  })

  it('if a favorite podcast, shows the favorite button which toggles favorite state', async () => {
    const toggleFavoriteMock = h.mock(podcastStore, 'toggleFavorite')
    const podcast = h.factory('podcast', { favorite: true })

    renderComponent(podcast)
    await h.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))

    expect(toggleFavoriteMock).toHaveBeenCalledWith(podcast)
  })
})
