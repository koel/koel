import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { albumStore, preferenceStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import MainContent from '@/components/layout/main-wrapper/MainContent.vue'
import AlbumArtOverlay from '@/components/ui/AlbumArtOverlay.vue'
import Visualizer from '@/components/ui/Visualizer.vue'

new class extends UnitTestCase {
  protected test () {
    it('has a translucent overlay per album', async () => {
      this.mock(albumStore, 'fetchThumbnail', 'https://foo/bar.jpg')

      const { getByTestId } = this.render(MainContent, {
        global: {
          stubs: {
            AlbumArtOverlay
          }
        }
      })

      eventBus.emit('SONG_STARTED', factory<Song>('song'))
      await this.tick(2) // re-render and fetch album thumbnail

      getByTestId('album-art-overlay')
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
      await this.tick(2) // re-render and fetch album thumbnail

      expect(await queryByTestId('album-art-overlay')).toBeNull()
    })

    it('toggles visualizer', async () => {
      const { getByTestId, queryByTestId } = this.render(MainContent, {
        global: {
          stubs: {
            Visualizer
          }
        }
      })

      eventBus.emit('TOGGLE_VISUALIZER')
      await this.tick()
      getByTestId('visualizer')

      eventBus.emit('TOGGLE_VISUALIZER')
      await this.tick()
      expect(await queryByTestId('visualizer')).toBeNull()
    })
  }
}
