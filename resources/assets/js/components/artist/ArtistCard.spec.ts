import { fireEvent } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { downloadService, playbackService } from '@/services'
import UnitTestCase from '@/__tests__/UnitTestCase'
import ArtistCard from './ArtistCard.vue'

let artist: Artist

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      artist = factory<Artist>('artist', {
        id: 3, // make sure it's not "Various Artists"
        name: 'Led Zeppelin',
        albums: factory<Album>('album', 4),
        songs: factory<Song>('song', 16)
      })
    })
  }

  protected test () {
    it('renders', () => {
      const { getByText, getByTestId } = this.render(ArtistCard, {
        props: {
          artist
        }
      })

      expect(getByTestId('name').innerText).equal('Led Zeppelin')
      getByText(/^4 albums\s+•\s+16 songs.+0 plays$/)
      getByTestId('shuffle-artist')
      getByTestId('download-artist')
    })

    it('downloads', async () => {
      const mock = this.mock(downloadService, 'fromArtist')

      const { getByTestId } = this.render(ArtistCard, {
        props: {
          artist
        }
      })

      await fireEvent.click(getByTestId('download-artist'))
      expect(mock).toHaveBeenCalledTimes(1)
    })

    it('shuffles', async () => {
      const mock = this.mock(playbackService, 'playAllByArtist')

      const { getByTestId } = this.render(ArtistCard, {
        props: {
          artist
        }
      })

      await fireEvent.click(getByTestId('shuffle-artist'))
      expect(mock).toHaveBeenCalled()
    })
  }
}
