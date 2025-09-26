import { screen, waitFor } from '@testing-library/vue'
import type { Mock } from 'vitest'
import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { podcastStore } from '@/stores/podcastStore'
import { playableStore as episodeStore } from '@/stores/playableStore'
import { playbackService } from '@/services/QueuePlaybackService'
import { queueStore } from '@/stores/queueStore'
import Router from '@/router'
import { eventBus } from '@/utils/eventBus'
import { useContextMenu } from '@/composables/useContextMenu'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import PodcastContextMenu from '@/components/podcast/PodcastContextMenu.vue'
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

    h.visit(`podcasts/${podcast.id}`)

    const rendered = h.render(Component, {
      global: {
        stubs: {
          PodcastItem: h.stub('podcast-item'),
          VirtualScroller: h.stub('virtual-scroller'),
        },
      },
    })

    await waitFor(() => {
      expect(resolvePodcastMock).toHaveBeenCalledWith(podcast.id)
      expect(fetchEpisodesMock).toHaveBeenCalledWith(podcast.id)
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

    const podcast = h.factory('podcast')
    const episodes = h.factory('episode', 2, { podcast_id: podcast.id })
    podcast.state.current_episode = episodes[0].id
    podcast.state.progresses = {
      [episodes[0].id]: 123,
      [episodes[1].id]: 456,
    }

    await renderComponent(podcast, episodes)
    await h.user.click(screen.getByRole('button', { name: 'Continue' }))

    expect(playMock).toHaveBeenCalledWith(episodes[0], 123)
  })

  it('refreshes podcast', async () => {
    const { podcast, fetchEpisodesMock } = await renderComponent()

    await h.tick()
    await h.user.click(screen.getByRole('button', { name: 'Refresh Podcast' }))

    expect(fetchEpisodesMock).toHaveBeenNthCalledWith(2, podcast.id, true)
  })

  it('has a Favorite button if podcast is favorite', async () => {
    const { podcast } = await renderComponent(h.factory('podcast', { favorite: true }))
    const toggleFavoriteMock = h.mock(podcastStore, 'toggleFavorite')

    await waitFor(async () =>
      await h.user.click(screen.getByRole('button', { name: 'Undo Favorite' })),
    )

    expect(toggleFavoriteMock).toHaveBeenCalledWith(podcast)
  })

  it('does not have a Favorite button if podcast is not favorite', async () => {
    await renderComponent(h.factory('podcast', { favorite: false }))
    expect(screen.queryByRole('button', { name: 'Favorite' })).toBeNull()
  })

  it('requests Actions menu', async () => {
    vi.mock('@/composables/useContextMenu')
    const { openContextMenu } = useContextMenu()
    const { podcast } = await renderComponent()

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'More Actions' }))
      await assertOpenContextMenu(openContextMenu as Mock, PodcastContextMenu, { podcast })
    })
  })

  it('goes back to podcast list if current one is unsubscribed', async () => {
    const goMock = h.mock(Router, 'go')
    const { podcast } = await renderComponent()
    eventBus.emit('PODCAST_UNSUBSCRIBED', podcast)

    expect(goMock).toHaveBeenCalledWith('/#/podcasts')
  })
})
