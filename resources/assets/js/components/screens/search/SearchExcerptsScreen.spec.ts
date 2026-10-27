import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { searchStore } from '@/stores/searchStore'
import { commonStore } from '@/stores/commonStore'
import { usePageTitle } from '@/composables/usePageTitle'
import Component from './SearchExcerptsScreen.vue'

describe('searchExcerptsScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      searchStore.state.keywords = ''
    },
  })

  it('executes searching when the search keyword is changed', async () => {
    const mock = h.mock(searchStore, 'excerptSearch')
    h.render(Component)

    eventBus.emit('SEARCH_KEYWORDS_CHANGED', 'search me')

    await waitFor(() => expect(mock).toHaveBeenCalledWith('search me'))
  })

  it('searches for keywords typed before the screen was first opened', async () => {
    const mock = h.mock(searchStore, 'excerptSearch')
    searchStore.state.keywords = 'beatles'

    h.render(Component)

    await waitFor(() => expect(mock).toHaveBeenCalledWith('beatles'))
  })

  it('keeps showing the loading state while the latest search is pending', async () => {
    let resolveEarlierSearch: () => void = () => {}

    h.mock(searchStore, 'excerptSearch')
      .mockReturnValueOnce(new Promise<void>(resolve => (resolveEarlierSearch = resolve)))
      .mockReturnValueOnce(new Promise<void>(() => {}))

    h.render(Component)

    eventBus.emit('SEARCH_KEYWORDS_CHANGED', 'foo')
    eventBus.emit('SEARCH_KEYWORDS_CHANGED', 'foobar')
    await waitFor(() => screen.getByRole('status'))

    resolveEarlierSearch()
    await h.tick()

    screen.getByRole('status')
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

  it.each([
    [true, true],
    [false, false],
  ])('shows podcast and radio results only when those are enabled (%s)', async (usesPodcasts, usesRadio) => {
    h.mock(searchStore, 'excerptSearch')
    commonStore.state.uses_podcasts = usesPodcasts
    commonStore.state.uses_radio = usesRadio
    h.render(Component)

    eventBus.emit('SEARCH_KEYWORDS_CHANGED', 'foo')
    await waitFor(() => screen.getByTestId('playable-excerpts'))

    expect(Boolean(screen.queryByTestId('podcast-excerpts'))).toBe(usesPodcasts)
    expect(Boolean(screen.queryByTestId('radio-station-excerpts'))).toBe(usesRadio)
  })
})
