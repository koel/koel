import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { podcastStore } from '@/stores/podcastStore'
import factory from '@/__tests__/factory'
import Component from './PodcastListScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      const fetchMock = this.mock(podcastStore, 'fetchAll')
      podcastStore.state.podcasts = factory('podcast', 9)

      await this.renderComponent()

      expect(screen.getAllByTestId('podcast-item')).toHaveLength(9)
      expect(fetchMock).toHaveBeenCalled()
    })

    it('shows a message when there are no podcasts', async () => {
      this.mock(podcastStore, 'fetchAll')
      podcastStore.state.podcasts = []
      await this.renderComponent()

      await waitFor(() => screen.getByTestId('screen-empty-state'))
    })

    it('shows all or only favorites upon toggling the button', async () => {
      podcastStore.state.podcasts = [
        ...factory('podcast', 3, { favorite: true }),
        ...factory('podcast', 6, { favorite: false }),
      ]

      this.mock(podcastStore, 'fetchAll')

      await this.renderComponent()
      expect(screen.getAllByTestId('podcast-item')).toHaveLength(9)

      await this.user.click(screen.getByRole('button', { name: 'Show favorites only' }))
      await waitFor(() => expect(screen.getAllByTestId('podcast-item')).toHaveLength(3))

      await this.user.click(screen.getByRole('button', { name: 'Show all' }))
      await waitFor(() => expect(screen.getAllByTestId('podcast-item')).toHaveLength(9))
    })
  }

  private async renderComponent () {
    const rendered = this.render(Component, {
      global: {
        stubs: {
          PodcastItem: this.stub('podcast-item'),
        },
      },
    })

    await this.router.activateRoute({ path: 'podcasts', screen: 'Podcasts' })
    await this.tick()

    return rendered
  }
}
