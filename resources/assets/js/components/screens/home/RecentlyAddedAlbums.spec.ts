import { expect, it } from 'vitest'
import { overviewStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import RecentlyAddedAlbums from './RecentlyAddedAlbums.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays the albums', () => {
      overviewStore.state.recentlyAddedAlbums = factory('album', 6)
      expect(this.render(RecentlyAddedAlbums, {
        global: {
          stubs: {
            AlbumCard: this.stub('album-card')
          }
        }
      }).getAllByTestId('album-card')).toHaveLength(6)
    })
  }
}
