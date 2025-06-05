import type { Ref } from 'vue'
import { ref } from 'vue'
import { expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { CurrentPlayableKey } from '@/symbols'
import { eventBus } from '@/utils/eventBus'
import Component from './SideSheet.vue'

new class extends UnitTestCase {
  protected beforeEach (cb?: Closure) {
    super.beforeEach(cb)

    // disable getting and saving the preferences
    this.mock(preferenceStore, 'update')
  }

  protected test () {
    it('renders without a current song', () => expect(this.renderComponent().rendered.html()).toMatchSnapshot())

    it('gets active tab from the preference', async () => {
      preferenceStore.active_extra_panel_tab = 'Lyrics'
      this.renderComponent(ref(factory('song')))

      await waitFor(() => {
        expect(screen.getByTestId<HTMLElement>('side-sheet-lyrics').style.display).toBe('')
      })
    })

    it('activates and sets active tab to the preference', async () => {
      preferenceStore.active_extra_panel_tab = 'Lyrics'
      this.renderComponent(ref(factory('song')))
      await this.tick()
      await this.user.click(screen.getByTestId('side-sheet-album-tab-header'))

      await waitFor(() => {
        expect(screen.getByTestId<HTMLElement>('side-sheet-lyrics').style.display).toBe('none')
        expect(screen.getByTestId<HTMLElement>('side-sheet-album').style.display).toBe('')
        expect(preferenceStore.active_extra_panel_tab).toBe('Album')
      })
    })

    it('resolves album and fetches album info for the current song', async () => {
      preferenceStore.active_extra_panel_tab = 'Album'

      const songRef = ref<Song | null>(null)

      const { resolveAlbumMock } = this.renderComponent(songRef)

      // trigger the side sheet to show album info
      songRef.value = factory('song')

      await waitFor(() => {
        screen.getByTestId('side-sheet-album')
        screen.getByTestId('album-info')
        expect(resolveAlbumMock).toHaveBeenCalledWith(songRef.value?.album_id)
      })
    })

    it('resolves artist and fetches artist info for the current song', async () => {
      preferenceStore.active_extra_panel_tab = 'Artist'

      const songRef = ref<Song | null>(null)

      const { resolveArtistMock } = this.renderComponent(songRef)

      // trigger the side sheet to show artist info
      songRef.value = factory('song')

      await waitFor(() => {
        screen.getByTestId('side-sheet-artist')
        screen.getByTestId('artist-info')
        expect(resolveArtistMock).toHaveBeenCalledWith(songRef.value?.artist_id)
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
        this.beAdmin().renderComponent()[0].getByRole('button', { name: 'New version available!' })
      })
    })

    it('logs out', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Log out' }))

      expect(emitMock).toHaveBeenCalledWith('LOG_OUT')
    })
  }

  private renderComponent (songRef: Ref<Song | null> = ref(null)) {
    const artist = factory('artist')
    const resolveArtistMock = this.mock(artistStore, 'resolve').mockResolvedValue(artist)

    const album = factory('album')
    const resolveAlbumMock = this.mock(albumStore, 'resolve').mockResolvedValue(album)

    const rendered = this.render(Component, {
      global: {
        stubs: {
          ProfileAvatar: this.stub('profile-avatar'),
          LyricsPane: this.stub('lyrics'),
          AlbumInfo: this.stub('album-info'),
          ArtistInfo: this.stub('artist-info'),
          YouTubeVideoList: this.stub('youtube-video-list'),
        },
        provide: {
          [<symbol>CurrentPlayableKey]: songRef,
        },
      },
    })

    return {
      rendered,
      resolveArtistMock,
      resolveAlbumMock,
    }
  }
}
