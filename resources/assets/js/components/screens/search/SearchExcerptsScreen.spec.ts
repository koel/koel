import { waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils/eventBus'
import { searchStore } from '@/stores/searchStore'
import Component from './SearchExcerptsScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('executes searching when the search keyword is changed', async () => {
      const mock = this.mock(searchStore, 'excerptSearch')
      this.render(Component)

      eventBus.emit('SEARCH_KEYWORDS_CHANGED', 'search me')

      await waitFor(() => expect(mock).toHaveBeenCalledWith('search me'))
    })
  }
}
