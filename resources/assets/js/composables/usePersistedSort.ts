import type { MaybeRefOrGetter } from 'vue'
import { toValue } from 'vue'
import { useLocalStorage } from '@/composables/useLocalStorage'

export const usePersistedSort = <F extends MaybeArray<PlayableListSortField> | null>(
  storageKeyPrefix: MaybeRefOrGetter<string>,
  defaultSortField: F,
) => {
  const { get, set } = useLocalStorage()

  const sortFieldKey = () => `${toValue(storageKeyPrefix)}-sort-field`
  const sortOrderKey = () => `${toValue(storageKeyPrefix)}-sort-order`

  const getSavedSort = (): { sortField: MaybeArray<PlayableListSortField> | F; sortOrder: SortOrder } => ({
    sortField: get<MaybeArray<PlayableListSortField>>(sortFieldKey()) ?? defaultSortField,
    sortOrder: get<SortOrder>(sortOrderKey()) ?? 'asc',
  })

  const saveSort = (field: MaybeArray<PlayableListSortField> | null, order: SortOrder) => {
    set(sortFieldKey(), field)
    set(sortOrderKey(), order)
  }

  return {
    getSavedSort,
    saveSort,
  }
}
