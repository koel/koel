import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { podcastStore } from '@/stores/podcastStore'
import { songStore as episodeStore } from '@/stores/songStore'
import factory from '@/__tests__/factory'
import { playbackService } from '@/services/playbackService'
import { queueStore } from '@/stores/queueStore'
import Component from './PodcastScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      await this.renderComponent()

      await waitFor(() => {
        screen.getByTestId('virtual-scroller')
        screen.getByText('A Brief History of Time')
      })
    })

    it('starts playing the podcast', async () => {
      const playMock = this.mock(playbackService, 'playFirstInQueue')
      await this.renderComponent()
      await this.tick()
      await this.user.click(screen.getByRole('button', { name: 'Start Listening' }))

      expect(playMock).toHaveBeenCalled()
    })

    it('resumes playback if currently queue item is part of podcast', async () => {
      const playMock = this.mock(playbackService, 'resume')

      const { episodes } = await this.renderComponent()
      queueStore.state.playables = episodes
      queueStore.state.playables[0].playback_state = 'Paused'

      await this.tick()
      await this.user.click(screen.getByRole('button', { name: 'Start Listening' }))

      expect(playMock).toHaveBeenCalled()
    })

    it('continues playing the current episode if any', async () => {
      const playMock = this.mock(playbackService, 'play')

      const { podcast, episodes } = await this.renderComponent()
      podcast.state.current_episode = episodes[0].id
      podcast.state.progresses = {
        [episodes[0].id]: 123,
        [episodes[1].id]: 456,
      }

      await this.tick()

      await this.user.click(screen.getByRole('button', { name: 'Continue' }))

      expect(playMock).toHaveBeenCalledWith(episodes[0], 123)
    })

    it('refreshes podcast', async () => {
      const { podcast, fetchEpisodesMock } = await this.renderComponent()

      await this.tick()
      await this.user.click(screen.getByRole('button', { name: 'Refresh Podcast' }))

      expect(fetchEpisodesMock).toHaveBeenNthCalledWith(2, podcast.id, true)
    })

    it('unsubscribes from podcast', async () => {
      const unsubscribeMock = this.mock(podcastStore, 'unsubscribe')
      const { podcast } = await this.renderComponent()

      await this.tick()
      await this.user.click(screen.getByRole('button', { name: 'Unsubscribe from Podcast' }))

      expect(unsubscribeMock).toHaveBeenCalledWith(podcast)
    })

    it('toggle favorites', async () => {
      const { podcast } = await this.renderComponent()
      const toggleFavoriteMock = this.mock(podcastStore, 'toggleFavorite')

      await this.tick()
      await this.user.click(screen.getByRole('button', { name: 'Favorite' }))

      expect(toggleFavoriteMock).toHaveBeenCalledWith(podcast)
    })
  }

  private async renderComponent (podcast?: Podcast, episodes?: Episode[]) {
    podcast = podcast || factory('podcast', {
      title: 'A Brief History of Time',
      favorite: false,
    })

    episodes = episodes || factory('episode', 9, { podcast_id: podcast.id })

    const resolvePodcastMock = this.mock(podcastStore, 'resolve').mockResolvedValue(podcast)
    const fetchEpisodesMock = this.mock(episodeStore, 'fetchForPodcast').mockResolvedValue(episodes)

    const rendered = this.render(Component, {
      global: {
        stubs: {
          PodcastItem: this.stub('podcast-item'),
          VirtualScroller: this.stub('virtual-scroller'),
        },
      },
    })

    await this.router.activateRoute({ path: `podcasts/${podcast.id}`, screen: 'Podcast' }, {
      id: podcast.id,
    })

    await this.tick()

    return {
      rendered,
      podcast,
      episodes,
      resolvePodcastMock,
      fetchEpisodesMock,
    }
  }
}
