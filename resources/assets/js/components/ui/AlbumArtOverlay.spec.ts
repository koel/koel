import { expect, it } from 'vitest'
import { albumStore } from '@/stores'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AlbumArtOverlay from './AlbumArtOverlay.vue'

let album: Album

new class extends UnitTestCase {
  private renderComponent () {
    album = factory<Album>('album')

    return this.render(AlbumArtOverlay, {
      props: {
        album
      }
    })
  }

  protected test () {
    it('fetches and displays the album thumbnail', async () => {
      const mock = this.mock(albumStore, 'fetchThumbnail')
      mock.mockResolvedValue('https://localhost/thumb.jpg')

      const { html } = this.renderComponent()
      await this.tick(2)

      expect(mock).toHaveBeenCalledWith(album)
      expect(html()).toMatchSnapshot()
    })

    it('displays nothing if fetching fails', async () => {
      const mock = this.mock(albumStore, 'fetchThumbnail', () => {
        throw new Error()
      })

      const { html } = this.renderComponent()
      await this.tick(2)

      expect(mock).toHaveBeenCalledWith(album)
      expect(html()).toMatchSnapshot()
    })
  }
}
