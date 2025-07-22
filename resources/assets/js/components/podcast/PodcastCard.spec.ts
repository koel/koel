import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { podcastStore } from '@/stores/podcastStore'
import Component from './PodcastCard.vue'

new class extends UnitTestCase {
  private renderComponent (podcast?: Podcast) {
    podcast = podcast || factory('podcast', {
      title: 'A Brief History of Time',
      author: 'Stephen Hawking',
      favorite: false,
    })

    const rendered = this.render(Component, {
      props: {
        podcast,
      },
      global: {
        stubs: {
          EpisodeProgress: this.stub('episode-progress-stub'),
        },
      },
    })

    return {
      rendered,
      podcast,
    }
  }

  protected test () {
    it('renders', () => {
      const { podcast } = this.renderComponent()
      screen.getByText('A Brief History of Time')
      screen.getByText('Stephen Hawking')

      expect(screen.getByTestId('title').getAttribute('href')).toBe(`/#/podcasts/${podcast.id}`)

      expect(screen.queryByRole('button', { name: 'Undo Favorite' })).toBeNull()
      expect(screen.queryByRole('button', { name: 'Favorite' })).toBeNull()
    })

    it('if a favorite podcast, shows the favorite button which toggles favorite state', async () => {
      const toggleFavoriteMock = this.mock(podcastStore, 'toggleFavorite')
      const podcast = factory('podcast', { favorite: true })

      this.renderComponent(podcast)
      await this.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))

      expect(toggleFavoriteMock).toHaveBeenCalledWith(podcast)
    })
  }
}
