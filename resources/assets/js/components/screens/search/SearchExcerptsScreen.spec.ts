import { describe, expect, it } from 'vitest'
import { waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { searchStore } from '@/stores/searchStore'
import Component from './SearchExcerptsScreen.vue'

describe('searchExcerptsScreen.vue', () => {
  const h = createHarness()

  it('executes searching when the search keyword is changed', async () => {
    const mock = h.mock(searchStore, 'excerptSearch')
    h.render(Component)

    eventBus.emit('SEARCH_KEYWORDS_CHANGED', 'search me')

    await waitFor(() => expect(mock).toHaveBeenCalledWith('search me'))
  })
})
