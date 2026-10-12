import type { Ref } from 'vue'
import { computed, nextTick, ref } from 'vue'
import { useErrorHandler } from '@/composables/useErrorHandler'

interface CursorPaginatedStore<SortField> {
  paginate: (params: {
    favorites_only: boolean
    cursor: string | null
    sort: SortField
    order: SortOrder
  }) => Promise<string | null>
  reset: () => void
}

interface Options<Item extends { favorite: boolean }, SortField> {
  store: CursorPaginatedStore<SortField>
  items: Ref<Item[]>
  favoritesOnly: Ref<boolean>
  sortField: Ref<SortField>
  sortOrder: Ref<SortOrder>
  onReset?: () => void
}

export const useCursorPaginatedList = <Item extends { favorite: boolean }, SortField>({
  store,
  items,
  favoritesOnly,
  sortField,
  sortOrder,
  onReset,
}: Options<Item, SortField>) => {
  const loading = ref(false)
  const cursor = ref<string | null>('')

  const moreAvailable = computed(() => cursor.value !== null)

  const displayedItems = computed(() => (favoritesOnly.value ? items.value.filter(item => item.favorite) : items.value))

  const noFavorites = computed(
    () => !loading.value && favoritesOnly.value && displayedItems.value.length === 0 && !moreAvailable.value,
  )

  const showSkeletons = computed(() => loading.value && items.value.length === 0)

  const fetchMore = async () => {
    if (loading.value || !moreAvailable.value) {
      return
    }

    loading.value = true

    try {
      cursor.value = await store.paginate({
        favorites_only: favoritesOnly.value,
        cursor: cursor.value,
        sort: sortField.value,
        order: sortOrder.value,
      })
    } catch (error: unknown) {
      useErrorHandler().handleHttpError(error)
    } finally {
      loading.value = false
    }
  }

  const refetchFromStart = async () => {
    cursor.value = ''
    store.reset()
    onReset?.()

    await nextTick()
    await fetchMore()
  }

  const sort = async (field: SortField, order: SortOrder) => {
    sortField.value = field
    sortOrder.value = order

    await refetchFromStart()
  }

  const toggleFavoritesOnly = async () => {
    favoritesOnly.value = !favoritesOnly.value

    await refetchFromStart()
  }

  return {
    loading,
    displayedItems,
    noFavorites,
    showSkeletons,
    fetchMore,
    sort,
    toggleFavoritesOnly,
  }
}
