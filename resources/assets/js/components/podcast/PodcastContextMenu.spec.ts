import Router from '@/router'
import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { playbackService } from '@/services/QueuePlaybackService'
import { playableStore as episodeStore } from '@/stores/playableStore'
import Component from './PodcastContextMenu.vue'

describe('podcastContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async (podcast?: Podcast) => {
    podcast = podcast || h.factory('podcast', {
      title: 'A Brief History of Time',
      author: 'Stephen Hawking',
      favorite: false,
    })

    const rendered = h.beAdmin().render(Component)
    eventBus.emit('PODCAST_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, podcast)
    await h.tick(2)

    return {
      ...rendered,
      podcast,
    }
  }

  it('renders', async () => expect((await renderComponent()).html()).toMatchSnapshot())

  it('plays all', async () => {
    h.createAudioPlayer()

    const episodes = h.factory('episode', 10)
    const fetchMock = h.mock(episodeStore, 'fetchEpisodesInPodcast').mockResolvedValue(episodes)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { podcast } = await renderComponent()
    await h.user.click(screen.getByText('Play All'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(podcast)
    expect(playMock).toHaveBeenCalledWith(episodes)
  })

  it('shuffles all', async () => {
    h.createAudioPlayer()

    const episodes = h.factory('episode', 10)
    const fetchMock = h.mock(episodeStore, 'fetchEpisodesInPodcast').mockResolvedValue(episodes)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { podcast } = await renderComponent()
    await h.user.click(screen.getByText('Shuffle All'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(podcast)
    expect(playMock).toHaveBeenCalledWith(episodes, true)
  })

  it('goes to podcast', async () => {
    const mock = h.mock(Router, 'go')
    const { podcast } = await renderComponent()

    await h.user.click(screen.getByText('Go to Podcast'))

    expect(mock).toHaveBeenCalledWith(`/#/podcasts/${podcast.id}`)
  })
})
