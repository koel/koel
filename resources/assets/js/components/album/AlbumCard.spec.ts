import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/QueuePlaybackService'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { eventBus } from '@/utils/eventBus'
import { albumStore } from '@/stores/albumStore'
import Component from './AlbumCard.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('renders external album', () => {
      expect(this.renderComponent(this.createAlbum({ is_external: true })).html()).toMatchSnapshot()
    })

    it('downloads', async () => {
      const mock = this.mock(downloadService, 'fromAlbum')
      this.renderComponent()

      await this.user.click(screen.getByTitle('Download all songs in the album IV'))

      expect(mock).toHaveBeenCalledTimes(1)
    })

    it('does not have an option to download if downloading is disabled', async () => {
      commonStore.state.allows_download = false
      this.renderComponent()

      expect(screen.queryByText('Download')).toBeNull()
    })

    it('shuffles', async () => {
      this.createAudioPlayer()

      const songs = factory('song', 10)
      const fetchMock = this.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)
      const shuffleMock = this.mock(playbackService, 'queueAndPlay').mockResolvedValue(void 0)
      const { album } = this.renderComponent(undefined, false)

      await this.user.click(screen.getByTitle('Shuffle all songs in the album IV'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(album)
      expect(shuffleMock).toHaveBeenCalledWith(songs, true)
    })

    it('requests context menu', async () => {
      const { album } = this.renderComponent()
      const emitMock = this.mock(eventBus, 'emit')
      await this.trigger(screen.getByTestId('artist-album-card'), 'contextMenu')

      expect(emitMock).toHaveBeenCalledWith('ALBUM_CONTEXT_MENU_REQUESTED', expect.any(MouseEvent), album)
    })

    it('shows release year', () => {
      this.renderComponent(this.createAlbum({ year: 1971 }), true)
      screen.getByText('1971')
    })

    it('does not show release year if not enabled via prop', () => {
      this.renderComponent(this.createAlbum({ year: 1971 }))
      expect(screen.queryByText('1971')).toBeNull()
    })

    it('if favorite, has a Favorite icon button that undoes favorite state', async () => {
      const album = this.createAlbum({ favorite: true })
      const toggleMock = this.mock(albumStore, 'toggleFavorite')
      this.renderComponent(album)

      await this.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))

      expect(toggleMock).toHaveBeenCalledWith(album)
    })

    it('if not favorite, does not have a Favorite icon button', async () => {
      this.renderComponent()
      expect(screen.queryByRole('button', { name: 'Undo Favorite' })).toBeNull()
    })
  }

  private createAlbum (overrides: Partial<Album> = {}): Album {
    return factory('album', {
      id: 'iv',
      name: 'IV',
      artist_id: 'led-zeppelin',
      artist_name: 'Led Zeppelin',
      cover: 'https://example.com/cover.jpg',
      favorite: false,
      ...overrides,
    })
  }

  private renderComponent (album?: Album, showReleaseYear = false) {
    album = album || this.createAlbum()

    const render = this.render(Component, {
      props: {
        showReleaseYear,
        album: album || this.createAlbum(),
      },
      global: {
        stubs: {
          AlbumArtistThumbnail: this.stub('thumbnail'),
        },
      },
    })

    return {
      ...render,
      album,
    }
  }
}
