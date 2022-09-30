import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { downloadService, playbackService } from '@/services'
import { commonStore, songStore } from '@/stores'
import AlbumContextMenu from './AlbumContextMenu.vue'

let album: Album

new class extends UnitTestCase {
  private async renderComponent (_album?: Album) {
    album = _album || factory<Album>('album', {
      name: 'IV',
      play_count: 30,
      song_count: 10,
      length: 123
    })

    const rendered = this.render(AlbumContextMenu)
    eventBus.emit('ALBUM_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 }, album)
    await this.tick(2)

    return rendered
  }

  protected test () {
    it('renders', async () => {
      const { html } = await this.renderComponent()
      expect(html()).toMatchSnapshot()
    })

    it('plays all', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      const { getByText } = await this.renderComponent()
      await getByText('Play All').click()
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(album)
      expect(playMock).toHaveBeenCalledWith(songs)
    })

    it('shuffles all', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      const { getByText } = await this.renderComponent()
      await getByText('Shuffle All').click()
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(album)
      expect(playMock).toHaveBeenCalledWith(songs, true)
    })

    it('downloads', async () => {
      const downloadMock = this.mock(downloadService, 'fromAlbum')

      const { getByText } = await this.renderComponent()
      await getByText('Download').click()

      expect(downloadMock).toHaveBeenCalledWith(album)
    })

    it('does not have an option to download if downloading is disabled', async () => {
      commonStore.state.allow_download = false
      const { queryByText } = await this.renderComponent()

      expect(queryByText('Download')).toBeNull()
    })

    it('goes to album', async () => {
      const mock = this.mock(this.router, 'go')
      const { getByText } = await this.renderComponent()

      await getByText('Go to Album').click()

      expect(mock).toHaveBeenCalledWith(`album/${album.id}`)
    })

    it('does not have an option to download or go to Unknown Album and Artist', async () => {
      const { queryByTestId } = await this.renderComponent(factory.states('unknown')<Album>('album'))

      expect(queryByTestId('view-album')).toBeNull()
      expect(queryByTestId('view-artist')).toBeNull()
      expect(queryByTestId('download')).toBeNull()
    })

    it('goes to artist', async () => {
      const mock = this.mock(this.router, 'go')
      const { getByText } = await this.renderComponent()

      await getByText('Go to Artist').click()

      expect(mock).toHaveBeenCalledWith(`artist/${album.artist_id}`)
    })
  }
}
