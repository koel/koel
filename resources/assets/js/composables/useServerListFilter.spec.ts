import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useServerListFilter } from './useServerListFilter'

describe('useServerListFilter', () => {
  const h = createHarness()

  beforeEach(() => vi.useFakeTimers())

  afterEach(() => vi.useRealTimers())

  const renderWithSearch = (search: (keywords: string) => Promise<string[]>) => {
    let filter!: ReturnType<typeof useServerListFilter<string>>

    h.render(
      defineComponent({
        setup: () => {
          filter = useServerListFilter(search)
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

  it('goes back to the full list when the keywords are cleared', async () => {
    const filter = renderWithSearch(vi.fn().mockResolvedValue(['Foo']))

    filter.keywords.value = 'foo'
    await vi.advanceTimersByTimeAsync(300)
    filter.keywords.value = ''
    await vi.advanceTimersByTimeAsync(300)

    expect(filter.filtering.value).toBe(false)
    expect(filter.results.value).toEqual([])
  })
})
