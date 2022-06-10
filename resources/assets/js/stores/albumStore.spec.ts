import UnitTestCase from '@/__tests__/UnitTestCase'
import { albumStore, artistStore } from '@/stores'

new class extends UnitTestCase {
  protected afterEach () {
    super.afterEach(() => {
      artistStore.state.artists = []
      albumStore.state.albums = []
    })
  }

  protected test () {
  }
}
