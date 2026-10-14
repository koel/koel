import Fuse from 'fuse.js'
import type { Ref } from 'vue'
import { isRef, shallowRef, triggerRef, watch } from 'vue'

type Path<T> = T extends object
  ? {
      [K in keyof T]: `${Exclude<K, symbol>}${'' | `.${Path<T[K]>}`}`
    }[keyof T]
  : never

export const useFuzzySearch = <T>(items: T[] | Ref<T[]>, keys: Path<T>[] | string[]) => {
  const fuse = new Fuse<T>([], { keys })
  const documents = shallowRef<T[]>([])
  let isIndexStale = true

  const setDocuments = (newDocuments: T[]) => {
    documents.value = newDocuments
    isIndexStale = true
    triggerRef(documents)
  }

  const ensureIndexIsFresh = (currentDocuments: T[]) => {
    if (isIndexStale) {
      fuse.setCollection(currentDocuments)
      isIndexStale = false
    }
  }

  if (isRef<T[]>(items)) {
    setDocuments(items.value)
    watch(items, () => setDocuments(items.value), { deep: 1 })
  } else {
    setDocuments(items)
  }

  const search = (query: string | null) => {
    query = query?.trim() ?? null
    const currentDocuments = documents.value

    if (!query) {
      return currentDocuments
    }

    ensureIndexIsFresh(currentDocuments)

    return fuse.search(query).map(result => result.item)
  }

  return {
    search,
    setDocuments,
  }
}
