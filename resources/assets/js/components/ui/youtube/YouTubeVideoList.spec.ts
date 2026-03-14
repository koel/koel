import { screen, waitFor } from '@testing-library/vue'
import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { youTubeService } from '@/services/youTubeService'
import YouTubeVideo from '@/components/ui/youtube/YouTubeVideoItem.vue'
import Component from './YouTubeVideoList.vue'

describe('youTubeVideoList', () => {
  const h = createHarness()

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('loads initial results and loads more on scroll', async () => {
    let intersectionCallback: IntersectionObserverCallback

    vi.stubGlobal(
      'IntersectionObserver',
      vi.fn().mockImplementation(function (cb: IntersectionObserverCallback) {
        intersectionCallback = cb
        return { observe: vi.fn(), unobserve: vi.fn(), disconnect: vi.fn() }
      }),
    )

    const song = h.factory('song')

    const searchMock = h
      .mock(youTubeService, 'searchVideosBySong')
      .mockResolvedValueOnce({
        nextPageToken: 'foo',
        items: h.factory('you-tube-video', 5),
      })
      .mockResolvedValueOnce({
        nextPageToken: '',
        items: h.factory('you-tube-video', 3),
      })

    h.render(Component, {
      props: { song },
      global: {
        stubs: { YouTubeVideo },
      },
    })

    await waitFor(() => {
      expect(searchMock).toHaveBeenNthCalledWith(1, song, '')
      expect(screen.getAllByRole('listitem')).toHaveLength(5)
    })

    // Simulate the sentinel becoming visible (infinite scroll trigger)
    intersectionCallback!([{ isIntersecting: true } as IntersectionObserverEntry], {} as IntersectionObserver)

    await waitFor(() => {
      expect(searchMock).toHaveBeenNthCalledWith(2, song, 'foo')
      expect(screen.getAllByRole('listitem')).toHaveLength(8)
    })
  })
})
