import { fireEvent, screen, waitFor } from '@testing-library/vue'
import { nextTick } from 'vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { podcastStore } from '@/stores/podcastStore'
import { getCoverBackgroundColor } from '@/utils/coverColor'
import Component from './PodcastItem.vue'

vi.mock('@/utils/coverColor', () => ({
  getCoverBackgroundColor: vi.fn().mockResolvedValue('hsl(0 50% 20%)'),
}))

describe('podcastItem.vue', () => {
  const h = createHarness()

  const renderComponent = (podcast?: Podcast) => {
    podcast =
      podcast ||
      h.factory('podcast').make({
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
    const podcast = h.factory('podcast').make({ favorite: true })

    renderComponent(podcast)
    await fireEvent(screen.getByTestId('favorite-button'), new CustomEvent('toggle'))

    expect(toggleFavoriteMock).toHaveBeenCalledWith(podcast)
  })

  it('extracts the cover color only once the item nears the viewport and keeps it after scrolling away', async () => {
    const intersectionCallbacks: IntersectionObserverCallback[] = []
    const defaultObserverImplementation = vi.mocked(window.IntersectionObserver).getMockImplementation()

    vi.mocked(window.IntersectionObserver).mockImplementation(function (callback: IntersectionObserverCallback) {
      intersectionCallbacks.push(callback)

      return { observe: vi.fn(), unobserve: vi.fn(), disconnect: vi.fn() }
    })

    vi.mocked(getCoverBackgroundColor).mockClear()

    const { podcast } = renderComponent()

    await waitFor(() => expect(intersectionCallbacks).toHaveLength(2))
    expect(getCoverBackgroundColor).not.toHaveBeenCalled()

    const reportIntersection = (isIntersecting: boolean, time: number) =>
      intersectionCallbacks.forEach(callback =>
        callback([{ isIntersecting, time } as IntersectionObserverEntry], {} as IntersectionObserver),
      )

    reportIntersection(true, 1)

    await waitFor(() => expect(getCoverBackgroundColor).toHaveBeenCalledWith(podcast.image))
    await waitFor(() => expect(screen.getByTestId('podcast-item').classList.contains('tinted')).toBe(true))

    reportIntersection(false, 2)
    await nextTick()

    expect(screen.getByTestId('podcast-item').classList.contains('tinted')).toBe(true)

    vi.mocked(window.IntersectionObserver).mockImplementation(defaultObserverImplementation!)
  })
})
