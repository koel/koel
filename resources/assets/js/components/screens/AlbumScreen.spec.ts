import { fireEvent, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { albumStore, commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import router from '@/router'
import { eventBus } from '@/utils'
import CloseModalBtn from '@/components/ui/BtnCloseModal.vue'
import AlbumScreen from './AlbumScreen.vue'

let album: Album

new class extends UnitTestCase {
  protected async renderComponent () {
    commonStore.state.use_last_fm = true

    album = factory<Album>('album', {
      id: 42,
      name: 'Led Zeppelin IV',
      artist_id: 123,
      artist_name: 'Led Zeppelin',
      song_count: 10,
      length: 1_603
    })

    const resolveAlbumMock = this.mock(albumStore, 'resolve').mockResolvedValue(album)

    const songs = factory<Song>('song', 13)
    const fetchSongsMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)

    const rendered = this.render(AlbumScreen, {
      props: {
        album: 42
      },
      global: {
        stubs: {
          CloseModalBtn,
          AlbumInfo: this.stub('album-info'),
          SongList: this.stub('song-list')
        }
      }
    })

    await waitFor(() => {
      expect(resolveAlbumMock).toHaveBeenCalledWith(album.id)
      expect(fetchSongsMock).toHaveBeenCalledWith(album.id)
    })

    await this.tick(2)

    return rendered
  }

  protected test () {
    it('shows and hides info', async () => {
      const { getByTitle, getByTestId, queryByTestId, html } = await this.renderComponent()
      expect(queryByTestId('album-info')).toBeNull()

      await fireEvent.click(getByTitle('View album information'))
      expect(queryByTestId('album-info')).not.toBeNull()

      await fireEvent.click(getByTestId('close-modal-btn'))
      expect(queryByTestId('album-info')).toBeNull()
    })

    it('downloads', async () => {
      const downloadMock = this.mock(downloadService, 'fromAlbum')
      const { getByText } = await this.renderComponent()

      await fireEvent.click(getByText('Download All'))

      expect(downloadMock).toHaveBeenCalledWith(album)
    })

    it('goes back to list if album is deleted', async () => {
      const goMock = this.mock(router, 'go')
      const byIdMock = this.mock(albumStore, 'byId', null)
      await this.renderComponent()

      eventBus.emit('SONGS_UPDATED')

      await waitFor(() => {
        expect(byIdMock).toHaveBeenCalledWith(album.id)
        expect(goMock).toHaveBeenCalledWith('albums')
      })
    })
  }
}
