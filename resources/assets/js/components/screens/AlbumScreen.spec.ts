import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { songStore } from '@/stores/songStore'
import { downloadService } from '@/services/downloadService'
import { resourcePermissionService } from '@/services/resourcePermissionService'
import { eventBus } from '@/utils/eventBus'
import Router from '@/router'
import AlbumScreen from './AlbumScreen.vue'

let album: Album

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      this.mock(resourcePermissionService, 'check').mockResolvedValue(true)
    })
  }

  protected test () {
    it('downloads', async () => {
      const downloadMock = this.mock(downloadService, 'fromAlbum')
      await this.renderComponent()

      await waitFor(async () => {
        await this.user.click(screen.getByRole('button', { name: 'Download All' }))
        expect(downloadMock).toHaveBeenCalledWith(album)
      })
    })

    it('goes back to list if album is deleted', async () => {
      const goMock = this.mock(Router, 'go')
      await this.renderComponent()
      await this.tick()

      eventBus.emit('SONGS_UPDATED', {
        songs: [],
        artists: [],
        albums: [],
        removed: {
          albums: [{
            id: album.id,
            name: album.name,
            artist_id: album.artist_id,
            cover: album.cover,
            created_at: album.created_at,
          }],
          artists: [],
        },
      })

      await waitFor(() => expect(goMock).toHaveBeenCalledWith('/#/albums'))
    })

    it('shows the song list', async () => {
      await this.renderComponent()
      await waitFor(async () => screen.getByTestId('song-list'))
    })

    it('shows other albums from the same artist', async () => {
      const albums = factory('album', 3)
      const fetchMock = this.mock(albumStore, 'fetchForArtist').mockResolvedValue(albums)
      await this.renderComponent('other-albums')

      albums.push(album)

      await waitFor(async () => {
        expect(fetchMock).toHaveBeenCalledWith(album.artist_id)
        expect(screen.getAllByTestId('album-card')).toHaveLength(3) // current album is excluded
      })
    })

    it('requests edit form', async () => {
      await this.renderComponent()

      const emitMock = this.mock(eventBus, 'emit')

      await waitFor(async () => {
        await this.user.click(screen.getByRole('button', { name: 'Edit' }))
        expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_ALBUM_FORM', album)
      })
    })
  }

  private async renderComponent (tab: 'songs' | 'other-albums' | 'information' = 'songs') {
    commonStore.state.uses_last_fm = true

    album = factory('album', {
      id: 'foo',
      name: 'Led Zeppelin IV',
      artist_id: 'bar',
      artist_name: 'Led Zeppelin',
    })

    const resolveAlbumMock = this.mock(albumStore, 'resolve').mockResolvedValue(album)

    const songs = factory('song', 13)
    const fetchSongsMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)

    await this.router.activateRoute({
      path: `albums/foo/${tab}`,
      screen: 'Album',
    }, {
      tab,
      id: 'foo',
    })

    this.beAdmin().render(AlbumScreen, {
      global: {
        stubs: {
          SongList: this.stub('song-list'),
          AlbumCard: this.stub('album-card'),
          AlbumInfo: this.stub('album-info'),
        },
      },
    })

    await waitFor(() => {
      expect(resolveAlbumMock).toHaveBeenCalledWith(album.id)
      expect(fetchSongsMock).toHaveBeenCalledWith(album.id)
    })
  }
}
