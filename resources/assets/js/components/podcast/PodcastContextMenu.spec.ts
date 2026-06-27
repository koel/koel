import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { shallowRef } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { ContextMenuKey } from '@/config/symbols'
import { playbackService } from '@/services/QueuePlaybackService'
import { playableStore as episodeStore } from '@/stores/playableStore'
import Component from './PodcastContextMenu.vue'
import { podcastStore } from '@/stores/podcastStore'

describe('podcastContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async (podcast?: Podcast) => {
    podcast =
      podcast ||
      h.factory('podcast').make({
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

  it('plays all', async () => {
    h.createAudioPlayer()

    const episodes = h.factory('episode').make(10)
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

    const episodes = h.factory('episode').make(10)
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
    const { podcast } = await renderComponent(h.factory('podcast').make({ favorite: true }))
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

  it('closes the menu after rating', async () => {
    h.mock(podcastStore, 'rate')
    const menu = shallowRef<any>({ component: Component, position: { top: 0, left: 0 } })
    const podcast = h.factory('podcast').make({ rating: 0 })

    h.actingAsAdmin().render(Component, {
      props: { podcast },
      global: { provide: { [ContextMenuKey as symbol]: menu } },
    })

    await h.user.click(screen.getByRole('radio', { name: 'Rate 4 of 5' }))

    expect(menu.value.component).toBeNull()
  })
})
