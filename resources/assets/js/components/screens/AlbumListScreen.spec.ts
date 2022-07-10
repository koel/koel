import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { albumStore } from '@/stores'
import AlbumListScreen from './AlbumListScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      albumStore.state.albums = factory<Album[]>('album', 9)

      expect(this.render(AlbumListScreen).getAllByTestId('album-card')).toHaveLength(9)
    })
  }
}
