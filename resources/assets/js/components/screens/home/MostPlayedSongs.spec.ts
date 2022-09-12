import { expect, it } from 'vitest'
import { overviewStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import MostPlayedSongs from './MostPlayedSongs.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays the songs', () => {
      overviewStore.state.mostPlayedSongs = factory<Song>('song', 6)
      expect(this.render(MostPlayedSongs).getAllByTestId('song-card')).toHaveLength(6)
    })
  }
}
