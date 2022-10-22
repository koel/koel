import { ref } from 'vue'
import { waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { albumStore, preferenceStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { CurrentSongKey } from '@/symbols'
import AlbumArtOverlay from '@/components/ui/AlbumArtOverlay.vue'
import MainContent from './MainContent.vue'

new class extends UnitTestCase {
  private renderComponent (hasCurrentSong = false) {
    return this.render(MainContent, {
      global: {
        provide: {
          [CurrentSongKey]: ref(factory<Song>('song'))
        },
        stubs: {
          AlbumArtOverlay,
          HomeScreen: this.stub(), // so that home overview requests are not made
          Visualizer: this.stub('visualizer'),
        },
      }
    })
  }

  protected test () {
    it('has a translucent overlay per album', async () => {
      this.mock(albumStore, 'fetchThumbnail').mockResolvedValue('http://test/foo.jpg')

      const { getByTestId } = this.renderComponent()

      await waitFor(() => getByTestId('album-art-overlay'))
    })

    it('does not have a translucent over if configured not so', async () => {
      preferenceStore.state.showAlbumArtOverlay = false

      const { queryByTestId } = this.renderComponent()

      await waitFor(() => expect(queryByTestId('album-art-overlay')).toBeNull())
    })

    it('toggles visualizer', async () => {
      const { getByTestId, queryByTestId } = this.renderComponent()

      eventBus.emit('TOGGLE_VISUALIZER')
      await waitFor(() => getByTestId('visualizer'))

      eventBus.emit('TOGGLE_VISUALIZER')
      await waitFor(() => expect(queryByTestId('visualizer')).toBeNull())
    })
  }
}
