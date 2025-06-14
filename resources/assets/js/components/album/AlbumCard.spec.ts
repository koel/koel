import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/playbackService'
import { commonStore } from '@/stores/commonStore'
import { songStore } from '@/stores/songStore'
import { eventBus } from '@/utils/eventBus'
import AlbumCard from './AlbumCard.vue'

let album: Album

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

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
      const songs = factory('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)
      const shuffleMock = this.mock(playbackService, 'queueAndPlay').mockResolvedValue(void 0)
      this.renderComponent()

      await this.user.click(screen.getByTitle('Shuffle all songs in the album IV'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(album)
      expect(shuffleMock).toHaveBeenCalledWith(songs, true)
    })

    it('requests context menu', async () => {
      this.renderComponent()
      const emitMock = this.mock(eventBus, 'emit')
      await this.trigger(screen.getByTestId('artist-album-card'), 'contextMenu')

      expect(emitMock).toHaveBeenCalledWith('ALBUM_CONTEXT_MENU_REQUESTED', expect.any(MouseEvent), album)
    })

    it('shows release year', () => {
      this.renderComponent(1971, true)
      screen.getByText('1971')
    })

    it('does not show release year if not enabled via prop', () => {
      this.renderComponent(1971)
      expect(screen.queryByText('1971')).toBeNull()
    })
  }

  private renderComponent (year: number | null = null, showReleaseYear = false) {
    album = factory('album', {
      year,
      id: 'iv',
      name: 'IV',
      artist_id: 'led-zeppelin',
      artist_name: 'Led Zeppelin',
      cover: 'https://example.com/cover.jpg',
    })

    return this.render(AlbumCard, {
      props: {
        album,
        showReleaseYear,
      },
      global: {
        stubs: {
          AlbumArtistThumbnail: this.stub('thumbnail'),
        },
      },
    })
  }
}
