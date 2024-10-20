import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { overviewStore } from '@/stores/overviewStore'
import RecentlyAddedAlbums from './RecentlyAddedAlbums.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays the albums', () => {
      overviewStore.state.recentlyAddedAlbums = factory('album', 6)
      expect(this.render(RecentlyAddedAlbums, {
        global: {
          stubs: {
            AlbumCard: this.stub('album-card'),
          },
        },
      }).getAllByTestId('album-card')).toHaveLength(6)
    })
  }
}
