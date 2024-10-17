import { waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import { albumStore } from '@/stores/albumStore'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { logger } from '@/utils/logger'
import AlbumArtOverlay from './AlbumArtOverlay.vue'

let albumId: number

new class extends UnitTestCase {
  protected test () {
    it('fetches and displays the album thumbnail', async () => {
      const fetchMock = this.mock(albumStore, 'fetchThumbnail').mockResolvedValue('http://test/thumb.jpg')

      const { html } = await this.renderComponent()

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(albumId)
        expect(html()).toMatchSnapshot()
      })
    })

    it('displays nothing if fetching fails', async () => {
      this.mock(logger, 'error')
      const fetchMock = this.mock(albumStore, 'fetchThumbnail').mockRejectedValue(new Error('Failed to fetch'))

      const { html } = await this.renderComponent()

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(albumId)
        expect(html()).toMatchSnapshot()
      })
    })
  }

  private async renderComponent () {
    albumId = 42

    const rendered = this.render(AlbumArtOverlay, {
      props: {
        album: albumId,
      },
    })

    await this.tick()

    return rendered
  }
}
