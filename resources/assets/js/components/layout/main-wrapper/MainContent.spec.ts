import { waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { albumStore, preferenceStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AlbumArtOverlay from '@/components/ui/AlbumArtOverlay.vue'
import MainContent from './MainContent.vue'

new class extends UnitTestCase {
  protected test () {
    it('has a translucent overlay per album', async () => {
      this.mock(albumStore, 'fetchThumbnail').mockResolvedValue('http://localhost/foo.jpg')

      const { getByTestId } = this.render(MainContent, {
        global: {
          stubs: {
            AlbumArtOverlay
          }
        }
      })

      eventBus.emit('SONG_STARTED', factory<Song>('song'))

      await waitFor(() => getByTestId('album-art-overlay'))
    })

    it('does not have a translucent over if configured not so', async () => {
      preferenceStore.state.showAlbumArtOverlay = false

      const { queryByTestId } = this.render(MainContent, {
        global: {
          stubs: {
            AlbumArtOverlay
          }
        }
      })

      eventBus.emit('SONG_STARTED', factory<Song>('song'))

      await waitFor(() => expect(queryByTestId('album-art-overlay')).toBeNull())
    })

    it('toggles visualizer', async () => {
      const { getByTestId, queryByTestId } = this.render(MainContent, {
        global: {
          stubs: {
            Visualizer: this.stub('visualizer')
          }
        }
      })

      eventBus.emit('TOGGLE_VISUALIZER')
      await waitFor(() => getByTestId('visualizer'))

      eventBus.emit('TOGGLE_VISUALIZER')
      await waitFor(() => expect(queryByTestId('visualizer')).toBeNull())
    })
  }
}
