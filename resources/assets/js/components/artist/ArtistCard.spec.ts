import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/playbackService'
import { commonStore } from '@/stores/commonStore'
import { songStore } from '@/stores/songStore'
import { eventBus } from '@/utils/eventBus'
import ArtistCard from './ArtistCard.vue'

new class extends UnitTestCase {
  private createArtist (overrides: Partial<Artist> = {}): Artist {
    return factory('artist', {
      id: 'led-zeppelin',
      name: 'Led Zeppelin',
      ...overrides,
    })
  }

  private renderComponent (artist?: Artist) {
    return this.render(ArtistCard, {
      props: {
        artist: artist || this.createArtist(),
      },
      global: {
        stubs: {
          AlbumArtistThumbnail: this.stub('thumbnail'),
        },
      },
    })
  }

  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('renders external artist', () => {
      expect(this.renderComponent(this.createArtist({ is_external: true })).html()).toMatchSnapshot()
    })

    it('downloads', async () => {
      const mock = this.mock(downloadService, 'fromArtist')
      this.renderComponent()

      await this.user.click(screen.getByTitle('Download all songs by Led Zeppelin'))
      expect(mock).toHaveBeenCalledOnce()
    })

    it('does not have an option to download if downloading is disabled', async () => {
      commonStore.state.allows_download = false
      this.renderComponent()

      expect(screen.queryByText('Download')).toBeNull()
    })

    it('shuffles', async () => {
      const artist = this.createArtist()
      const songs = factory('song', 16)
      const fetchMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      this.renderComponent(artist)

      await this.user.click(screen.getByTitle('Shuffle all songs by Led Zeppelin'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(artist)
      expect(playMock).toHaveBeenCalledWith(songs, true)
    })

    it('requests context menu', async () => {
      const artist = this.createArtist()
      this.renderComponent(artist)
      const emitMock = this.mock(eventBus, 'emit')
      await this.trigger(screen.getByTestId('artist-album-card'), 'contextMenu')

      expect(emitMock).toHaveBeenCalledWith('ARTIST_CONTEXT_MENU_REQUESTED', expect.any(MouseEvent), artist)
    })
  }
}
