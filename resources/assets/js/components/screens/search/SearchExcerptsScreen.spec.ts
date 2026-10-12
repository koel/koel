import { describe, expect, it } from 'vite-plus/test'
import { waitFor } from '@testing-library/vue'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { searchStore } from '@/stores/searchStore'
import { usePageTitle } from '@/composables/usePageTitle'
import Component from './SearchExcerptsScreen.vue'

describe('searchExcerptsScreen.vue', () => {
  const h = createHarness()

  it('executes searching when the search keyword is changed', async () => {
    const mock = h.mock(searchStore, 'excerptSearch')
    h.render(Component)

    eventBus.emit('SEARCH_KEYWORDS_CHANGED', 'search me')

    await waitFor(() => expect(mock).toHaveBeenCalledWith('search me'))
  })

  it('puts the search terms in the page title', async () => {
    h.mock(searchStore, 'excerptSearch')
    const DocumentTitle = defineComponent({ setup: () => usePageTitle().syncDocumentTitle(), template: '<div />' })
    h.visit('/search')
    h.render(DocumentTitle)
    h.render(Component)

    eventBus.emit('SEARCH_KEYWORDS_CHANGED', 'Dio')

    await waitFor(() => expect(document.title).toBe('Dio – Koel'))
  })
})
