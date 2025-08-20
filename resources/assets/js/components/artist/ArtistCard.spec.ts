import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/QueuePlaybackService'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { eventBus } from '@/utils/eventBus'
import { artistStore } from '@/stores/artistStore'
import Component from './ArtistCard.vue'

new class extends UnitTestCase {
  private createArtist (overrides: Partial<Artist> = {}): Artist {
    return factory('artist', {
      id: 'led-zeppelin',
      name: 'Led Zeppelin',
      favorite: false,
      ...overrides,
    })
  }

  private renderComponent (artist?: Artist) {
    artist = artist || this.createArtist()
    const rendered = this.render(Component, {
      props: {
        artist,
      },
      global: {
        stubs: {
          AlbumArtistThumbnail: this.stub('thumbnail'),
        },
      },
    })

    return {
      ...rendered,
      artist,
    }
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
      this.createAudioPlayer()

      const songs = factory('song', 16)
      const fetchMock = this.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      const { artist } = this.renderComponent()

      await this.user.click(screen.getByTitle('Shuffle all songs by Led Zeppelin'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(artist)
      expect(playMock).toHaveBeenCalledWith(songs, true)
    })

    it('requests context menu', async () => {
      const { artist } = this.renderComponent()
      const emitMock = this.mock(eventBus, 'emit')
      await this.trigger(screen.getByTestId('artist-album-card'), 'contextMenu')

      expect(emitMock).toHaveBeenCalledWith('ARTIST_CONTEXT_MENU_REQUESTED', expect.any(MouseEvent), artist)
    })

    it('if favorite, has a Favorite icon button that undoes favorite state', async () => {
      const artist = this.createArtist({ favorite: true })
      const toggleMock = this.mock(artistStore, 'toggleFavorite')
      this.renderComponent(artist)

      await this.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))

      expect(toggleMock).toHaveBeenCalledWith(artist)
    })

    it('if not favorite, does not have a Favorite icon button', async () => {
      this.renderComponent()
      expect(screen.queryByRole('button', { name: 'Undo Favorite' })).toBeNull()
    })
  }
}
