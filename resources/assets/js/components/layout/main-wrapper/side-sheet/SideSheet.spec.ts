import type { Ref } from 'vue'
import { ref } from 'vue'
import type { Mock } from 'vitest'
import { expect, it } from 'vitest'
import type { RenderResult } from '@testing-library/vue'
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
  protected test () {
    it('renders without a current song', () => expect(this.renderComponent()[0].html()).toMatchSnapshot())

    it('sets the active tab to the preference', async () => {
      preferenceStore.active_extra_panel_tab = 'YouTube'
      this.renderComponent(ref(factory('song')))
      const tab = screen.getByTestId<HTMLElement>('side-sheet-youtube')

      expect(tab.style.display).toBe('none')
      await this.tick()
      expect(tab.style.display).toBe('')
    })

    it('fetches info for the current song', async () => {
      commonStore.state.uses_you_tube = true

      const song = factory('song')
      const songRef = ref<Song | null>(null)

      const [, resolveArtistMock, resolveAlbumMock] = this.renderComponent(songRef)
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

  private renderComponent (songRef: Ref<Song | null> = ref(null)): [RenderResult, Mock, Mock] {
    const artist = factory('artist')
    const resolveArtistMock = this.mock(artistStore, 'resolve').mockResolvedValue(artist)

    const album = factory('album')
    const resolveAlbumMock = this.mock(albumStore, 'resolve').mockResolvedValue(album)

    const rendered = this.render(Component, {
      global: {
        stubs: {
          ProfileAvatar: this.stub(),
          LyricsPane: this.stub('lyrics'),
          AlbumInfo: this.stub('album-info'),
          ArtistInfo: this.stub('artist-info'),
          YouTubeVideoList: this.stub('youtube-video-list'),
          ExtraPanelTabHeader: this.stub(),
        },
        provide: {
          [<symbol>CurrentPlayableKey]: songRef,
        },
      },
    })

    return [rendered, resolveArtistMock, resolveAlbumMock]
  }
}
