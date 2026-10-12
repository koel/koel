import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { albumStore } from '@/stores/albumStore'
import { createHarness } from '@/__tests__/TestHarness'
import { logger } from '@/utils/logger'
import Component from './AlbumArtOverlay.vue'

describe('albumArtOverlay.vue', () => {
  const h = createHarness()

  const renderComponent = async () => {
    const albumId = 'foo'

    const rendered = h.render(Component, {
      props: {
        album: albumId,
      },
    })

    await h.tick()

    return {
      ...rendered,
      albumId,
    }
  }

  it('fetches and displays the album thumbnail', async () => {
    const fetchMock = h.mock(albumStore, 'fetchThumbnail').mockResolvedValue('http://test/thumb.jpg')

    const { albumId } = await renderComponent()

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(albumId)
      expect(screen.getByTestId('album-art-overlay').style.backgroundImage).toBe('url("http://test/thumb.jpg")')
    })
  })

  it('displays nothing if fetching fails', async () => {
    h.mock(logger, 'error')
    const fetchMock = h.mock(albumStore, 'fetchThumbnail').mockRejectedValue(new Error('Failed to fetch'))

    const { albumId } = await renderComponent()

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(albumId)
      expect(screen.getByTestId('album-art-overlay').style.backgroundImage).toBe('none')
    })
  })
})
