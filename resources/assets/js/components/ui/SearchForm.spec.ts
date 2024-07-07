import Router from '@/router'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { screen } from '@testing-library/vue'
import { eventBus } from '@/utils'
import SearchForm from './SearchForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('sets focus into search box when requested', async () => {
      this.render(SearchForm)

      eventBus.emit('FOCUS_SEARCH_FIELD')

      expect(screen.getByRole('searchbox')).toBe(document.activeElement)
    })

    it('goes to search screen when search box is focused', async () => {
      const mock = this.mock(Router, 'go')
      this.render(SearchForm)

      await this.user.click(screen.getByRole('searchbox'))

      expect(mock).toHaveBeenCalledWith('search')
    })

    it('emits an event when search query is changed', async () => {
      const mock = this.mock(eventBus, 'emit')
      this.render(SearchForm)

      await this.type(screen.getByRole('searchbox'), 'hey')

      expect(mock).toHaveBeenCalledWith('SEARCH_KEYWORDS_CHANGED', 'hey')
    })

    it('goes to the search screen if the form is submitted', async () => {
      const goMock = this.mock(Router, 'go')
      this.render(SearchForm)

      await this.type(screen.getByRole('searchbox'), 'hey')
      await this.user.click(screen.getByRole('button', { name: 'Search' }))

      expect(goMock).toHaveBeenCalledWith('search')
    })
  }
}
