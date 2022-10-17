import { fireEvent } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { downloadService, playbackService } from '@/services'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore, songStore } from '@/stores'
import ArtistCard from './ArtistCard.vue'

let artist: Artist

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      artist = factory<Artist>('artist', {
        id: 42,
        name: 'Led Zeppelin'
      })
    })
  }

  protected test () {
    it('renders', () => {
      const { html } = this.render(ArtistCard, {
        props: {
          artist
        }
      })

      expect(html()).toMatchSnapshot()
    })

    it('downloads', async () => {
      const mock = this.mock(downloadService, 'fromArtist')

      const { getByTestId } = this.render(ArtistCard, {
        props: {
          artist
        }
      })

      await fireEvent.click(getByTestId('download-artist'))
      expect(mock).toHaveBeenCalledOnce()
    })

    it('does not have an option to download if downloading is disabled', async () => {
      commonStore.state.allow_download = false

      const { queryByTestId } = this.render(ArtistCard, {
        props: {
          artist
        }
      })

      expect(queryByTestId('download-artist')).toBeNull()
    })

    it('shuffles', async () => {
      const songs = factory<Song>('song', 16)
      const fetchMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      const { getByTestId } = this.render(ArtistCard, {
        props: {
          artist
        }
      })

      await fireEvent.click(getByTestId('shuffle-artist'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(artist)
      expect(playMock).toHaveBeenCalledWith(songs, true)
    })
  }
}
