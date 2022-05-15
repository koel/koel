import { fireEvent } from '@testing-library/vue'
import { expect, it } from 'vitest'
import { downloadService, playbackService } from '@/services'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AlbumCard from './AlbumCard.vue'

let album: Album

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      album = factory<Album>('album', {
        name: 'IV',
        songs: factory<Song>('song', 10)
      })
    })
  }

  protected test () {
    it('renders', () => {
      const { getByText, getByTestId } = this.render(AlbumCard, {
        props: {
          album
        }
      })

      expect(getByTestId('name').textContent).toBe('IV')
      getByText(/^10 songs.+0 plays$/)
      getByTestId('shuffle-album')
      getByTestId('download-album')
    })

    it('downloads', async () => {
      const mock = this.mock(downloadService, 'fromAlbum')

      const { getByTestId } = this.render(AlbumCard, {
        props: {
          album
        }
      })

      await fireEvent.click(getByTestId('download-album'))
      expect(mock).toHaveBeenCalledTimes(1)
    })

    it('shuffles', async () => {
      const mock = this.mock(playbackService, 'playAllInAlbum')

      const { getByTestId } = this.render(AlbumCard, {
        props: {
          album
        }
      })

      await fireEvent.click(getByTestId('shuffle-album'))
      expect(mock).toHaveBeenCalled()
    })
  }
}
