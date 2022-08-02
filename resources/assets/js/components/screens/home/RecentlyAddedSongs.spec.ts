import { expect, it } from 'vitest'
import { overviewStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import RecentlyAddedSongs from './RecentlyAddedSongs.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays the songs', () => {
      overviewStore.state.recentlyAddedSongs = factory<Song[]>('song', 6)
      expect(this.render(RecentlyAddedSongs).getAllByTestId('song-card')).toHaveLength(6)
    })
  }
}
