import Router from '@/router'
import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { downloadService, playbackService } from '@/services'
import { commonStore, songStore } from '@/stores'
import AlbumContextMenu from './AlbumContextMenu.vue'

let album: Album

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => expect((await this.renderComponent()).html()).toMatchSnapshot())

    it('plays all', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      await this.renderComponent()
      await this.user.click(screen.getByText('Play All'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(album)
      expect(playMock).toHaveBeenCalledWith(songs)
    })

    it('shuffles all', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      await this.renderComponent()
      await this.user.click(screen.getByText('Shuffle All'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(album)
      expect(playMock).toHaveBeenCalledWith(songs, true)
    })

    it('downloads', async () => {
      const downloadMock = this.mock(downloadService, 'fromAlbum')
      await this.renderComponent()

      await this.user.click(screen.getByText('Download'))

      expect(downloadMock).toHaveBeenCalledWith(album)
    })

    it('does not have an option to download if downloading is disabled', async () => {
      commonStore.state.allows_download = false
      await this.renderComponent()

      expect(screen.queryByText('Download')).toBeNull()
    })

    it('goes to album', async () => {
      const mock = this.mock(Router, 'go')
      await this.renderComponent()

      await this.user.click(screen.getByText('Go to Album'))

      expect(mock).toHaveBeenCalledWith(`album/${album.id}`)
    })

    it('does not have an option to download or go to Unknown Album and Artist', async () => {
      await this.renderComponent(factory.states('unknown')<Album>('album'))

      expect(screen.queryByText('Go to Album')).toBeNull()
      expect(screen.queryByText('Go to Artist')).toBeNull()
      expect(screen.queryByText('Download')).toBeNull()
    })

    it('goes to artist', async () => {
      const mock = this.mock(Router, 'go')
      await this.renderComponent()

      await this.user.click(screen.getByText('Go to Artist'))

      expect(mock).toHaveBeenCalledWith(`artist/${album.artist_id}`)
    })
  }

  private async renderComponent (_album?: Album) {
    album = _album || factory<Album>('album', {
      name: 'IV'
    })

    const rendered = this.render(AlbumContextMenu)
    eventBus.emit('ALBUM_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, album)
    await this.tick(2)

    return rendered
  }
}
