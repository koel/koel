import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { youTubeService } from '@/services/youTubeService'
import Btn from '@/components/ui/form/Btn.vue'
import YouTubeVideo from '@/components/ui/youtube/YouTubeVideoItem.vue'
import Component from './YouTubeVideoList.vue'

describe('youTubeVideoList.vue', () => {
  const h = createHarness()

  it('functions', async () => {
    const song = h.factory('song')

    const searchMock = h.mock(youTubeService, 'searchVideosBySong').mockResolvedValueOnce({
      nextPageToken: 'foo',
      items: h.factory('you-tube-video', 5),
    }).mockResolvedValueOnce({
      nextPageToken: 'bar',
      items: h.factory('you-tube-video', 3),
    })

    h.render(Component, {
      props: {
        song,
      },
      global: {
        stubs: {
          Btn,
          YouTubeVideo,
        },
      },
    })

    await waitFor(() => {
      expect(searchMock).toHaveBeenNthCalledWith(1, song, '')
      expect(screen.getAllByRole('listitem')).toHaveLength(5)
    })

    await h.user.click(screen.getByRole('button', { name: 'Load More' }))

    await waitFor(() => {
      expect(searchMock).toHaveBeenNthCalledWith(2, song, 'foo')
      expect(screen.getAllByRole('listitem')).toHaveLength(8)
    })
  })
})
