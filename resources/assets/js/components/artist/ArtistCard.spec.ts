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
        name: 'Led Zeppelin',
        album_count: 4,
        play_count: 124,
        song_count: 16
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

      expect(getByTestId('name').textContent).toBe('Led Zeppelin')
      getByText(/^4 albums\s+•\s+16 songs.+124 plays$/)
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
