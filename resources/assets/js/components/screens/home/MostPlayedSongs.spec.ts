import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { overviewStore } from '@/stores/overviewStore'
import MostPlayedSongs from './MostPlayedSongs.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays the songs', () => {
      overviewStore.state.mostPlayedSongs = factory('song', 6)
      expect(this.render(MostPlayedSongs, {
        global: {
          stubs: {
            SongCard: this.stub('song-card'),
          },
        },
      }).getAllByTestId('song-card')).toHaveLength(6)
    })
  }
}
