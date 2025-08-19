import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { overviewStore } from '@/stores/overviewStore'
import TopAlbums from './TopAlbums.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays the albums', () => {
      overviewStore.state.mostPlayedAlbums = factory('album', 6)
      expect(this.render(TopAlbums, {
        global: {
          stubs: {
            AlbumCard: this.stub('album-card'),
          },
        },
      }).getAllByTestId('album-card')).toHaveLength(6)
    })
  }
}
