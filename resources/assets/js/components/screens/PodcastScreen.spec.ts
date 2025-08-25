import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { podcastStore } from '@/stores/podcastStore'
import { playableStore as episodeStore } from '@/stores/playableStore'
import { playbackService } from '@/services/QueuePlaybackService'
import { queueStore } from '@/stores/queueStore'
import Component from './PodcastScreen.vue'

describe('podcastScreen.vue', () => {
  const h = createHarness()

  const renderComponent = async (podcast?: Podcast, episodes?: Episode[]) => {
    podcast = podcast || h.factory('podcast', {
      title: 'A Brief History of Time',
      favorite: false,
    })

    episodes = episodes || h.factory('episode', 9, { podcast_id: podcast.id })

    const resolvePodcastMock = h.mock(podcastStore, 'resolve').mockResolvedValue(podcast)
    const fetchEpisodesMock = h.mock(episodeStore, 'fetchEpisodesInPodcast').mockResolvedValue(episodes)

    const rendered = h.render(Component, {
      global: {
        stubs: {
          PodcastItem: h.stub('podcast-item'),
          VirtualScroller: h.stub('virtual-scroller'),
        },
      },
    })

    await h.router.activateRoute({ path: `podcasts/${podcast.id}`, screen: 'Podcast' }, {
      id: podcast.id,
    })

    await h.tick()

    return {
      rendered,
      podcast,
      episodes,
      resolvePodcastMock,
      fetchEpisodesMock,
    }
  }

  it('renders', async () => {
    await renderComponent()

    await waitFor(() => {
      screen.getByTestId('virtual-scroller')
      screen.getByText('A Brief History of Time')
    })
  })

  it('starts playing the podcast', async () => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'playFirstInQueue')
    await renderComponent()
    await h.tick()
    await h.user.click(screen.getByRole('button', { name: 'Start Listening' }))

    expect(playMock).toHaveBeenCalled()
  })

  it('resumes playback if currently queue item is part of podcast', async () => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'resume')

    const { episodes } = await renderComponent()
    queueStore.state.playables = episodes
    queueStore.state.playables[0].playback_state = 'Paused'

    await h.tick()
    await h.user.click(screen.getByRole('button', { name: 'Start Listening' }))

    expect(playMock).toHaveBeenCalled()
  })

  it('continues playing the current episode if any', async () => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'play')

    const { podcast, episodes } = await renderComponent()
    podcast.state.current_episode = episodes[0].id
    podcast.state.progresses = {
      [episodes[0].id]: 123,
      [episodes[1].id]: 456,
    }

    await h.tick()

    await h.user.click(screen.getByRole('button', { name: 'Continue' }))

    expect(playMock).toHaveBeenCalledWith(episodes[0], 123)
  })

  it('refreshes podcast', async () => {
    const { podcast, fetchEpisodesMock } = await renderComponent()

    await h.tick()
    await h.user.click(screen.getByRole('button', { name: 'Refresh Podcast' }))

    expect(fetchEpisodesMock).toHaveBeenNthCalledWith(2, podcast.id, true)
  })

  it('unsubscribes from podcast', async () => {
    const unsubscribeMock = h.mock(podcastStore, 'unsubscribe')
    const { podcast } = await renderComponent()

    await h.tick()
    await h.user.click(screen.getByRole('button', { name: 'Unsubscribe from Podcast' }))

    expect(unsubscribeMock).toHaveBeenCalledWith(podcast)
  })

  it('toggle favorites', async () => {
    const { podcast } = await renderComponent()
    const toggleFavoriteMock = h.mock(podcastStore, 'toggleFavorite')

    await h.tick()
    await h.user.click(screen.getByRole('button', { name: 'Favorite' }))

    expect(toggleFavoriteMock).toHaveBeenCalledWith(podcast)
  })
})
