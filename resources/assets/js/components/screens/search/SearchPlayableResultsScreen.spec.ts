import { describe, expect, it } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { searchStore } from '@/stores/searchStore'
import { usePageTitle } from '@/composables/usePageTitle'
import SearchPlayableResultsScreen from './SearchPlayableResultsScreen.vue'

describe('searchPlayableResultsScreen.vue', () => {
  const h = createHarness()

  it('searches for prop query on created', () => {
    const resetResultMock = h.mock(searchStore, 'resetPlayableResultState')
    const searchMock = h.mock(searchStore, 'playableSearch')

    h.visit('/search/songs?q=foo').render(SearchPlayableResultsScreen)

    expect(resetResultMock).toHaveBeenCalled()
    expect(searchMock).toHaveBeenCalledWith('foo')
  })

  it('puts the search terms in the page title', async () => {
    h.mock(searchStore, 'resetPlayableResultState')
    h.mock(searchStore, 'playableSearch')
    const DocumentTitle = defineComponent({ setup: () => usePageTitle().syncDocumentTitle(), template: '<div />' })
    h.visit('/search/songs?q=Holy%20Diver')
    h.render(DocumentTitle)
    h.render(SearchPlayableResultsScreen)
    await h.tick()

    expect(document.title).toBe('Holy Diver – Koel')
  })
})
