import { fireEvent, screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { podcastStore } from '@/stores/podcastStore'
import Component from './PodcastItem.vue'

describe('podcastItem.vue', () => {
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
          EpisodeProgress: h.stub('episode-progress'),
          FavoriteButton: h.stub('favorite-button', true),
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

    expect(screen.getByTestId('podcast-item').getAttribute('href')).toBe(`/#/podcasts/${podcast.id}`)

    expect(screen.queryByRole('button', { name: 'Undo Favorite' })).toBeNull()
    expect(screen.queryByRole('button', { name: 'Favorite' })).toBeNull()
  })

  it('if a favorite podcast, shows the favorite button which toggles favorite state', async () => {
    const toggleFavoriteMock = h.mock(podcastStore, 'toggleFavorite')
    const podcast = h.factory('podcast', { favorite: true })

    renderComponent(podcast)
    await fireEvent(screen.getByTestId('favorite-button'), new CustomEvent('toggle'))

    expect(toggleFavoriteMock).toHaveBeenCalledWith(podcast)
  })
})
