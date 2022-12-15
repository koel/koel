import { ref, Ref } from 'vue'
import { expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import { albumStore, artistStore, commonStore, preferenceStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { CurrentSongKey } from '@/symbols'
import { eventBus } from '@/utils'
import ExtraDrawer from './ExtraDrawer.vue'

new class extends UnitTestCase {
  private renderComponent (songRef: Ref<Song | null> = ref(null)) {
    return this.render(ExtraDrawer, {
      global: {
        stubs: {
          ProfileAvatar: this.stub(),
          LyricsPane: this.stub('lyrics'),
          AlbumInfo: this.stub('album-info'),
          ArtistInfo: this.stub('artist-info'),
          YouTubeVideoList: this.stub('youtube-video-list'),
          ExtraPanelTabHeader: this.stub()
        },
        provide: {
          [<symbol>CurrentSongKey]: songRef
        }
      }
    })
  }

  protected test () {
    it('renders without a current song', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('sets the active tab to the preference', async () => {
      preferenceStore.activeExtraPanelTab = 'YouTube'
      this.renderComponent(ref(factory<Song>('song')))
      const tab = screen.getByTestId<HTMLElement>('extra-drawer-youtube')

      expect(tab.style.display).toBe('none')
      await this.tick()
      expect(tab.style.display).toBe('')
    })

    it('fetches info for the current song', async () => {
      commonStore.state.use_you_tube = true
      const artist = factory<Artist>('artist')
      const resolveArtistMock = this.mock(artistStore, 'resolve').mockResolvedValue(artist)

      const album = factory<Album>('album')
      const resolveAlbumMock = this.mock(albumStore, 'resolve').mockResolvedValue(album)

      const song = factory<Song>('song')

      const songRef = ref<Song | null>(null)

      this.renderComponent(songRef)
      songRef.value = song

      await waitFor(() => {
        expect(resolveArtistMock).toHaveBeenCalledWith(song.artist_id)
        expect(resolveAlbumMock).toHaveBeenCalledWith(song.album_id)
        ;['lyrics', 'album-info', 'artist-info', 'youtube-video-list'].forEach(id => screen.getByTestId(id))
      })
    })

    it('shows About Koel model', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'About Koel' }))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_ABOUT_KOEL')
    })

    it('notifies new version', async () => {
      it('shows new version', () => {
        commonStore.state.current_version = 'v1.0.0'
        commonStore.state.latest_version = 'v1.0.1'
        this.actingAsAdmin().renderComponent().getByRole('button', { name: 'New version available!' })
      })
    })

    it('logs out', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Log out' }))

      expect(emitMock).toHaveBeenCalledWith('LOG_OUT')
    })
  }
}
