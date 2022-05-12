import { expect, it, vi } from 'vitest'
import router from '@/router'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import SearchForm from './SearchForm.vue'
import { fireEvent } from '@testing-library/vue'
import { eventBus } from '@/utils'

new class extends ComponentTestCase {
  protected test () {
    // skipping because of unstable getRootNode() issues
    it.skip('sets focus into search box when requested', async () => {
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
      vi.useFakeTimers()
      const mock = this.mock(eventBus, 'emit')
      const { getByRole } = this.render(SearchForm)

      await fireEvent.update(getByRole('searchbox'), 'hey')

      vi.advanceTimersByTime(500)
      expect(mock).toHaveBeenCalledWith('SEARCH_KEYWORDS_CHANGED', 'hey')

      vi.useRealTimers()
    })
  }
}
