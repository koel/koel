import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { searchStore } from '@/stores'
import { eventBus } from '@/utils'
import SearchExceptsScreen from './SearchExcerptsScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('executes searching when the search keyword is changed', async () => {
      const mock = this.mock(searchStore, 'excerptSearch')
      this.render(SearchExceptsScreen)
      eventBus.emit('SEARCH_KEYWORDS_CHANGED', 'search me')
      await this.tick()

      expect(mock).toHaveBeenCalledWith('search me')
    })
  }
}
