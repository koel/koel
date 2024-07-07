import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore } from '@/stores'
import { screen } from '@testing-library/vue'
import { mediaInfoService } from '@/services'
import ArtistInfoComponent from './ArtistInfo.vue'

let artist: Artist

new class extends UnitTestCase {
  protected test () {
    it.each<[MediaInfoDisplayMode]>([['aside'], ['full']])('renders in %s mode', async (mode) => {
      await this.renderComponent(mode)

      if (mode === 'aside') {
        screen.getByTestId('thumbnail')
      } else {
        expect(screen.queryByTestId('thumbnail')).toBeNull()
      }

      expect(screen.getByTestId('artist-info').classList.contains(mode)).toBe(true)
    })
  }

  private async renderComponent (mode: MediaInfoDisplayMode = 'aside', info?: ArtistInfo) {
    commonStore.state.uses_last_fm = true
    info = info ?? factory('artist-info')
    artist = factory('artist', { name: 'Led Zeppelin' })

    const fetchMock = this.mock(mediaInfoService, 'fetchForArtist').mockResolvedValue(info)

    const rendered = this.render(ArtistInfoComponent, {
      props: {
        artist,
        mode
      },
      global: {
        stubs: {
          ArtistThumbnail: this.stub('thumbnail')
        }
      }
    })

    await this.tick(1)
    expect(fetchMock).toHaveBeenCalledWith(artist)

    return rendered
  }
}
