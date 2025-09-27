import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/QueuePlaybackService'
import { playableStore as episodeStore } from '@/stores/playableStore'
import Component from './PodcastContextMenu.vue'
import { podcastStore } from '@/stores/podcastStore'

describe('podcastContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async (podcast?: Podcast) => {
    podcast = podcast || h.factory('podcast', {
      title: 'A Brief History of Time',
      author: 'Stephen Hawking',
      favorite: false,
    })

    const rendered = h.actingAsAdmin().render(Component, {
      props: {
        podcast,
      },
    })

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

  it('favorites', async () => {
    const { podcast } = await renderComponent()
    const favoriteMock = h.mock(podcastStore, 'toggleFavorite')

    await h.user.click(screen.getByText('Favorite'))

    expect(favoriteMock).toHaveBeenCalledWith(podcast)
  })

  it('undoes favorite', async () => {
    const { podcast } = await renderComponent(h.factory('podcast', { favorite: true }))
    const favoriteMock = h.mock(podcastStore, 'toggleFavorite')

    await h.user.click(screen.getByText('Undo Favorite'))

    expect(favoriteMock).toHaveBeenCalledWith(podcast)
  })

  it('unsubscribes', async () => {
    const { podcast } = await renderComponent()
    const unsubMock = h.mock(podcastStore, 'unsubscribe')

    await h.user.click(screen.getByText('Unsubscribe'))

    expect(unsubMock).toHaveBeenCalledWith(podcast)
  })
})
