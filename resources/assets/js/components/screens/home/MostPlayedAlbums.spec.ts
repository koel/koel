import { expect, it } from 'vitest'
import { overviewStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import MostPlayedAlbums from './MostPlayedAlbums.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays the albums', () => {
      overviewStore.state.mostPlayedAlbums = factory<Album[]>('album', 6)
      expect(this.render(MostPlayedAlbums).getAllByTestId('album-card')).toHaveLength(6)
    })
  }
}
