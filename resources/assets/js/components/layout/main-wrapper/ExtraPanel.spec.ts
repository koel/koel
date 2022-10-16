import { ref, Ref } from 'vue'
import { expect, it } from 'vitest'
import { fireEvent, waitFor } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import { albumStore, artistStore, commonStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { CurrentSongKey } from '@/symbols'
import ExtraPanel from './ExtraPanel.vue'
import { eventBus } from '@/utils'

new class extends UnitTestCase {
  private renderComponent (songRef: Ref<Song | null> = ref(null)) {
    return this.render(ExtraPanel, {
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
          [CurrentSongKey]: songRef
        }
      }
    })
  }

  protected test () {
    it('renders without a current song', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('fetches info for the current song', async () => {
      commonStore.state.use_you_tube = true
      const artist = factory<Artist>('artist')
      const resolveArtistMock = this.mock(artistStore, 'resolve').mockResolvedValue(artist)

      const album = factory<Album>('album')
      const resolveAlbumMock = this.mock(albumStore, 'resolve').mockResolvedValue(album)

      const song = factory<Song>('song')

      const songRef = ref<Song | null>(null)

      const { getByTestId } = this.renderComponent(songRef)
      songRef.value = song

      await waitFor(() => {
        expect(resolveArtistMock).toHaveBeenCalledWith(song.artist_id)
        expect(resolveAlbumMock).toHaveBeenCalledWith(song.album_id)
        ;['lyrics', 'album-info', 'artist-info', 'youtube-video-list'].forEach(id => getByTestId(id))
      })
    })

    it('shows About Koel model', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      const { getByTitle } = this.renderComponent()

      await fireEvent.click(getByTitle('About Koel'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_ABOUT_KOEL')
    })

    it('logs out', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      const { getByTitle } = this.renderComponent()

      await fireEvent.click(getByTitle('Log out'))

      expect(emitMock).toHaveBeenCalledWith('LOG_OUT')
    })
  }
}
