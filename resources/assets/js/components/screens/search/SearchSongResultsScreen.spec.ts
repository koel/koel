import { expect, it } from 'vitest'
import { searchStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SearchSongResultsScreen from './SearchSongResultsScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('searches for prop query on created', () => {
      const resetResultMock = this.mock(searchStore, 'resetSongResultState')
      const searchMock = this.mock(searchStore, 'songSearch')
      this.render(SearchSongResultsScreen, {
        props: {
          q: 'search me'
        }
      })

      expect(resetResultMock).toHaveBeenCalled()
      expect(searchMock).toHaveBeenCalledWith('search me')
    })
  }
}
