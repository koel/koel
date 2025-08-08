import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { searchStore } from '@/stores/searchStore'
import SearchPlayableResultsScreen from './SearchPlayableResultsScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('searches for prop query on created', () => {
      const resetResultMock = this.mock(searchStore, 'resetPlayableResultState')
      const searchMock = this.mock(searchStore, 'playableSearch')

      this.router.activateRoute({ path: 'search-playables', screen: 'Search.Playables' }, { q: 'search me' })
      this.render(SearchPlayableResultsScreen)

      expect(resetResultMock).toHaveBeenCalled()
      expect(searchMock).toHaveBeenCalledWith('search me')
    })
  }
}
