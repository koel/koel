import { waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import { albumStore } from '@/stores/albumStore'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { logger } from '@/utils/logger'
import Component from './AlbumArtOverlay.vue'

new class extends UnitTestCase {
  protected test () {
    it('fetches and displays the album thumbnail', async () => {
      const fetchMock = this.mock(albumStore, 'fetchThumbnail').mockResolvedValue('http://test/thumb.jpg')

      const { albumId, html } = await this.renderComponent()

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(albumId)
        expect(html()).toMatchSnapshot()
      })
    })

    it('displays nothing if fetching fails', async () => {
      this.mock(logger, 'error')
      const fetchMock = this.mock(albumStore, 'fetchThumbnail').mockRejectedValue(new Error('Failed to fetch'))

      const { albumId, html } = await this.renderComponent()

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(albumId)
        expect(html()).toMatchSnapshot()
      })
    })
  }

  private async renderComponent () {
    const albumId = 'foo'

    const rendered = this.render(Component, {
      props: {
        album: albumId,
      },
    })

    await this.tick()

    return {
      ...rendered,
      albumId,
    }
  }
}
