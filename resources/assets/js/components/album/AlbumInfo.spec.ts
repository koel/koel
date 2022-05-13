import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AlbumInfo from './AlbumInfo.vue'
import AlbumThumbnail from '@/components/ui/AlbumArtistThumbnail.vue'

new class extends UnitTestCase {
  protected test () {
    it.each([['sidebar'], ['full']])('renders in %s mode', async (mode: string) => {
      const { getByTestId } = this.render(AlbumInfo, {
        props: {
          mode,
          album: factory<Album>('album')
        },
        global: {
          stubs: {
            AlbumThumbnail
          }
        }
      })

      getByTestId('album-artist-thumbnail')

      const element = getByTestId('album-info')
      expect(element.classList.contains(mode)).toBe(true)
    })

    it('triggers showing full wiki', async () => {
      const album = factory<Album>('album')

      const { getByText } = this.render(AlbumInfo, {
        props: {
          album
        }
      })

      await fireEvent.click(getByText('Full Wiki'))
      getByText(album.info!.wiki!.full)
    })
  }
}
