import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import { downloadService, playbackService } from '@/services'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AlbumCard from './AlbumCard.vue'
import { commonStore, songStore } from '@/stores'

let album: Album

new class extends UnitTestCase {
  private renderComponent () {
    album = factory<Album>('album', {
      id: 42,
      name: 'IV',
      artist_id: 17,
      artist_name: 'Led Zeppelin'
    })

    return this.render(AlbumCard, {
      props: {
        album
      },
      global: {
        stubs: {
          AlbumArtistThumbnail: this.stub('thumbnail')
        }
      }
    })
  }

  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('downloads', async () => {
      const mock = this.mock(downloadService, 'fromAlbum')
      this.renderComponent()

      await this.user.click(screen.getByTitle('Download all songs in the album IV'))

      expect(mock).toHaveBeenCalledTimes(1)
    })

    it('does not have an option to download if downloading is disabled', async () => {
      commonStore.state.allow_download = false
      this.renderComponent()

      expect(screen.queryByText('Download')).toBeNull()
    })

    it('shuffles', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)
      const shuffleMock = this.mock(playbackService, 'queueAndPlay').mockResolvedValue(void 0)
      this.renderComponent()

      await this.user.click(screen.getByTitle('Shuffle all songs in the album IV'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(album)
      expect(shuffleMock).toHaveBeenCalledWith(songs, true)
    })
  }
}
