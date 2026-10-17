import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { defineComponent, ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useServerListSearch } from './useServerListSearch'

describe('useServerListSearch', () => {
  const h = createHarness()

  beforeEach(() => vi.useFakeTimers())

  afterEach(() => vi.useRealTimers())

  const renderWithSearch = (search: (keywords: string) => Promise<string[]>, searchAgainOn = ref(false)) => {
    let filter!: ReturnType<typeof useServerListSearch<string>>

    h.render(
      defineComponent({
        setup: () => {
          filter = useServerListSearch(search, searchAgainOn)
        },
        template: '<div />',
      }),
    )

    return filter
  }

  it('searches the server once typing settles', async () => {
    const search = vi.fn().mockResolvedValue(['Foo'])
    const filter = renderWithSearch(search)

    filter.keywords.value = 'f'
    filter.keywords.value = 'fo'
    filter.keywords.value = ' foo '
    await vi.advanceTimersByTimeAsync(300)

    expect(search).toHaveBeenCalledOnce()
    expect(search).toHaveBeenCalledWith('foo')
    expect(filter.filtering.value).toBe(true)
    expect(filter.results.value).toEqual(['Foo'])
  })

  it('keeps the results of the latest search when an earlier one responds late', async () => {
    let resolveEarlierSearch: (results: string[]) => void = () => {}

    const search = vi
      .fn()
      .mockImplementationOnce(() => new Promise<string[]>(resolve => (resolveEarlierSearch = resolve)))
      .mockResolvedValueOnce(['Latest'])

    const filter = renderWithSearch(search)

    filter.keywords.value = 'old'
    await vi.advanceTimersByTimeAsync(300)
    filter.keywords.value = 'new'
    await vi.advanceTimersByTimeAsync(300)
    resolveEarlierSearch(['Stale'])
    await vi.advanceTimersByTimeAsync(0)

    expect(filter.results.value).toEqual(['Latest'])
    expect(filter.searching.value).toBe(false)
  })

  it('drops a late response once the user types again', async () => {
    let resolveEarlierSearch: (results: string[]) => void = () => {}
    const search = vi
      .fn()
      .mockImplementationOnce(() => new Promise<string[]>(resolve => (resolveEarlierSearch = resolve)))
    const filter = renderWithSearch(search)

    filter.keywords.value = 'old'
    await vi.advanceTimersByTimeAsync(300)
    filter.keywords.value = 'older'
    resolveEarlierSearch(['Stale'])
    await vi.advanceTimersByTimeAsync(0)

    expect(filter.results.value).toEqual([])
    expect(filter.searching.value).toBe(true)
  })

  it('searches again when told to', async () => {
    const searchAgainOn = ref(false)
    const search = vi.fn().mockResolvedValue(['Foo'])
    const filter = renderWithSearch(search, searchAgainOn)

    filter.keywords.value = 'foo'
    await vi.advanceTimersByTimeAsync(300)
    searchAgainOn.value = true
    await vi.advanceTimersByTimeAsync(0)

    expect(search).toHaveBeenCalledTimes(2)
  })

  it('goes back to the full list when the keywords are cleared', async () => {
    const filter = renderWithSearch(vi.fn().mockResolvedValue(['Foo']))

    filter.keywords.value = 'foo'
    await vi.advanceTimersByTimeAsync(300)
    filter.keywords.value = ''
    await vi.advanceTimersByTimeAsync(300)

    expect(filter.filtering.value).toBe(false)
    expect(filter.results.value).toEqual([])
  })

  it('counts as searching from the first keystroke until the results arrive', async () => {
    const filter = renderWithSearch(vi.fn().mockResolvedValue([]))

    filter.keywords.value = 'm'
    await vi.advanceTimersByTimeAsync(0)

    expect(filter.searching.value).toBe(true)

    await vi.advanceTimersByTimeAsync(300)

    expect(filter.searching.value).toBe(false)
  })
})
