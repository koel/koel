import { expect, it } from 'vitest'
import router from '@/router'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { fireEvent } from '@testing-library/vue'
import { eventBus } from '@/utils'
import SearchForm from './SearchForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('sets focus into search box when requested', async () => {
      const { getByRole } = this.render(SearchForm)

      eventBus.emit('FOCUS_SEARCH_FIELD')

      expect(getByRole('searchbox')).toBe(document.activeElement)
    })

    it('goes to search screen when search box is focused', async () => {
      const mock = this.mock(router, 'go')
      const { getByRole } = this.render(SearchForm)

      await fireEvent.focus(getByRole('searchbox'))

      expect(mock).toHaveBeenCalledWith('search')
    })

    it('emits an event when search query is changed', async () => {
      const mock = this.mock(eventBus, 'emit')
      const { getByRole } = this.render(SearchForm)

      await fireEvent.update(getByRole('searchbox'), 'hey')

      expect(mock).toHaveBeenCalledWith('SEARCH_KEYWORDS_CHANGED', 'hey')
    })
  }
}
