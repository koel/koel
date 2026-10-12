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

  const setDocuments = (newDocuments: T[]) => {
    fuse.setCollection(newDocuments)
    documents.value = newDocuments
    triggerRef(documents)
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

    return query ? fuse.search(query).map(result => result.item) : currentDocuments
  }

  return {
    search,
    setDocuments,
  }
}
