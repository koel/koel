import Router from '@/router'
import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { albumStore, commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { eventBus } from '@/utils'
import AlbumScreen from './AlbumScreen.vue'

let album: Album

new class extends UnitTestCase {
  protected test () {
    it('downloads', async () => {
      const downloadMock = this.mock(downloadService, 'fromAlbum')
      await this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Download All' }))

      expect(downloadMock).toHaveBeenCalledWith(album)
    })

    it('goes back to list if album is deleted', async () => {
      const goMock = this.mock(Router, 'go')
      const byIdMock = this.mock(albumStore, 'byId', null)
      await this.renderComponent()

      eventBus.emit('SONGS_UPDATED')

      await waitFor(() => {
        expect(byIdMock).toHaveBeenCalledWith(album.id)
        expect(goMock).toHaveBeenCalledWith('albums')
      })
    })

    it('shows the song list', async () => {
      await this.renderComponent()
      screen.getByTestId('song-list')
    })

    it('shows other albums from the same artist', async () => {
      const albums = factory('album', 3)
      albums.push(album)
      const fetchMock = this.mock(albumStore, 'fetchForArtist').mockResolvedValue(albums)
      await this.renderComponent()

      await this.user.click(screen.getByRole('radio', { name: 'Other Albums' }))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(album.artist_id)
        expect(screen.getAllByTestId('album-card')).toHaveLength(3) // current album is excluded
      })
    })
  }

  private async renderComponent () {
    commonStore.state.uses_last_fm = true

    album = factory('album', {
      id: 42,
      name: 'Led Zeppelin IV',
      artist_id: 123,
      artist_name: 'Led Zeppelin'
    })

    const resolveAlbumMock = this.mock(albumStore, 'resolve').mockResolvedValue(album)

    const songs = factory('song', 13)
    const fetchSongsMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)

    await this.router.activateRoute({
      path: 'albums/42',
      screen: 'Album'
    }, { id: '42' })

    this.render(AlbumScreen, {
      global: {
        stubs: {
          SongList: this.stub('song-list'),
          AlbumCard: this.stub('album-card'),
          AlbumInfo: this.stub('album-info')
        }
      }
    })

    await waitFor(() => {
      expect(resolveAlbumMock).toHaveBeenCalledWith(album.id)
      expect(fetchSongsMock).toHaveBeenCalledWith(album.id)
    })

    await this.tick(2)
  }
}
