import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import ArtistInfo from './ArtistInfo.vue'
import ArtistThumbnail from '@/components/ui/AlbumArtistThumbnail.vue'

new class extends ComponentTestCase {
  protected test () {
    it.each([['sidebar'], ['full']])('renders in %s mode', async (mode: string) => {
      const { getByTestId } = this.render(ArtistInfo, {
        props: {
          mode,
          artist: factory<Artist>('artist')
        },
        global: {
          stubs: {
            ArtistThumbnail
          }
        }
      })

      getByTestId('album-artist-thumbnail')

      const element = getByTestId('artist-info')
      expect(element.classList.contains(mode)).toBe(true)
    })

    it('triggers showing full wiki', async () => {
      const artist = factory<Artist>('artist')

      const { getByText } = this.render(ArtistInfo, {
        props: {
          artist
        }
      })

      await fireEvent.click(getByText('Full Bio'))
      getByText(artist.info!.bio!.full)
    })
  }
}
