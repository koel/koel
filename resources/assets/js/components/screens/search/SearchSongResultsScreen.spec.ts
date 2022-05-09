import { expect, it } from 'vitest'
import { searchStore } from '@/stores'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import SearchSongResultsScreen from './SearchSongResultsScreen.vue'

new class extends ComponentTestCase {
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
