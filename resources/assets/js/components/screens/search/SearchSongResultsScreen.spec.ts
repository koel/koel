import { expect, it } from 'vitest'
import { searchStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SearchSongResultsScreen from './SearchSongResultsScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('searches for prop query on created', () => {
      const resetResultMock = this.mock(searchStore, 'resetPlayableResultState')
      const searchMock = this.mock(searchStore, 'playableSearch')

      this.router.activateRoute({ path: 'search-songs', screen: 'Search.Songs' }, { q: 'search me' })
      this.render(SearchSongResultsScreen)

      expect(resetResultMock).toHaveBeenCalled()
      expect(searchMock).toHaveBeenCalledWith('search me')
    })
  }
}
