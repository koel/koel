import { describe, expect, it, vi } from 'vite-plus/test'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useCursorPaginatedList } from './useCursorPaginatedList'

describe('useCursorPaginatedList', () => {
  createHarness()

  const setup = (pages: (string | null)[] = [null]) => {
    const items = ref<{ id: string; favorite: boolean }[]>([])
    const favoritesOnly = ref(false)
    const sortField = ref('name')
    const sortOrder = ref<SortOrder>('asc')
    const onReset = vi.fn()

    const store = {
      paginate: vi.fn().mockImplementation(async () => pages.shift() ?? null),
      reset: vi.fn(),
    }

    const list = useCursorPaginatedList({ store, items, favoritesOnly, sortField, sortOrder, onReset })

    return { list, store, items, favoritesOnly, sortField, sortOrder, onReset }
  }

  it('fetches pages until there is no next cursor', async () => {
    const { list, store } = setup(['page-2', null])

    await list.fetchMore()
    await list.fetchMore()
    await list.fetchMore()

    expect(store.paginate).toHaveBeenCalledTimes(2)
    expect(store.paginate).toHaveBeenLastCalledWith({
      favorites_only: false,
      cursor: 'page-2',
      sort: 'name',
      order: 'asc',
    })
  })

  it('starts over with the new order when sorting', async () => {
    const { list, store, onReset, sortField, sortOrder } = setup()

    await list.fetchMore()
    await list.sort('year', 'desc')

    expect(sortField.value).toBe('year')
    expect(sortOrder.value).toBe('desc')
    expect(store.reset).toHaveBeenCalledOnce()
    expect(onReset).toHaveBeenCalledOnce()
    expect(store.paginate).toHaveBeenLastCalledWith({ favorites_only: false, cursor: '', sort: 'year', order: 'desc' })
  })

  it('shows only favorites, and says so when there are none', async () => {
    const { list, items, favoritesOnly } = setup()
    items.value = [
      { id: 'a', favorite: true },
      { id: 'b', favorite: false },
    ]

    await list.toggleFavoritesOnly()

    expect(favoritesOnly.value).toBe(true)
    expect(list.displayedItems.value.map(item => item.id)).toEqual(['a'])
    expect(list.noFavorites.value).toBe(false)

    items.value = [{ id: 'b', favorite: false }]

    expect(list.noFavorites.value).toBe(true)
  })
})
