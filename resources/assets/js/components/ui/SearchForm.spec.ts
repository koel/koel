import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Router from '@/router'
import Component from './SearchForm.vue'

describe('searchForm.vue', () => {
  const h = createHarness()

  it('sets focus into search box when requested', async () => {
    h.render(Component)

    eventBus.emit('FOCUS_SEARCH_FIELD')

    expect(screen.getByRole('searchbox')).toBe(document.activeElement)
  })

  it('goes to search screen when search box is focused', async () => {
    const mock = h.mock(Router, 'go')
    h.render(Component)

    await h.user.click(screen.getByRole('searchbox'))

    expect(mock).toHaveBeenCalledWith('/#/search')
  })

  it('emits an event when search query is changed', async () => {
    const mock = h.mock(eventBus, 'emit')
    h.render(Component)

    await h.type(screen.getByRole('searchbox'), 'hey')

    expect(mock).toHaveBeenCalledWith('SEARCH_KEYWORDS_CHANGED', 'hey')
  })

  it('goes to the search screen if the form is submitted', async () => {
    const goMock = h.mock(Router, 'go')
    h.render(Component)

    await h.type(screen.getByRole('searchbox'), 'hey')
    await h.user.click(screen.getByRole('button', { name: 'Search' }))

    expect(goMock).toHaveBeenCalledWith('/#/search')
  })
})
