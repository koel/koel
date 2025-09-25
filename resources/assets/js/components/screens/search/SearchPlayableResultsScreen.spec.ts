import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { searchStore } from '@/stores/searchStore'
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
})
