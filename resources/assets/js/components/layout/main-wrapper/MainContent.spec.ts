import { ref } from 'vue'
import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { albumStore, preferenceStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { CurrentSongKey } from '@/symbols'
import AlbumArtOverlay from '@/components/ui/AlbumArtOverlay.vue'
import MainContent from './MainContent.vue'

new class extends UnitTestCase {
  private renderComponent () {
    return this.render(MainContent, {
      global: {
        provide: {
          [<symbol>CurrentSongKey]: ref(factory<Song>('song'))
        },
        stubs: {
          AlbumArtOverlay,
          HomeScreen: this.stub(), // so that home overview requests are not made
          Visualizer: this.stub('visualizer')
        }
      }
    })
  }

  protected test () {
    it('has a translucent overlay per album', async () => {
      this.mock(albumStore, 'fetchThumbnail').mockResolvedValue('http://test/foo.jpg')

      this.renderComponent()

      await waitFor(() => screen.getByTestId('album-art-overlay'))
    })

    it('does not have a translucent over if configured not so', async () => {
      preferenceStore.state.showAlbumArtOverlay = false

      this.renderComponent()

      await waitFor(() => expect(screen.queryByTestId('album-art-overlay')).toBeNull())
    })
  }
}
