import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import { commonStore } from '@/stores'
import { songInfoService } from '@/services'
import { eventBus } from '@/utils'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import ExtraPanel from './ExtraPanel.vue'

new class extends ComponentTestCase {
  private renderComponent () {
    return this.render(ExtraPanel, {
      props: {
        song: factory<Song>('song')
      },
      global: {
        stubs: {
          LyricsPane: this.stub(),
          AlbumInfo: this.stub(),
          ArtistInfo: this.stub(),
          YouTubeVideoList: this.stub()
        }
      }
    })
  }

  protected test () {
    it('has a YouTube tab if using YouTube ', () => {
      commonStore.state.useYouTube = true
      const { getByTestId } = this.renderComponent()

      getByTestId('extra-tab-youtube')
    })

    it('does not have a YouTube tab if not using YouTube', async () => {
      commonStore.state.useYouTube = false
      const { queryByTestId } = this.renderComponent()

      expect(await queryByTestId('extra-tab-youtube')).toBeNull()
    })

    it.each([['extra-tab-lyrics'], ['extra-tab-album'], ['extra-tab-artist']])('switches to "%s" tab', async (id) => {
      const { getByTestId, container } = this.renderComponent()

      await fireEvent.click(getByTestId(id))

      expect(container.querySelector('[aria-selected=true]')).toBe(getByTestId(id))
    })

    it('fetches song info when a new song is played', () => {
      this.renderComponent()
      const song = factory<Song>('song')
      const mock = this.mock(songInfoService, 'fetch', song)

      eventBus.emit('SONG_STARTED', song)

      expect(mock).toHaveBeenCalledWith(song)
    })
  }
}
