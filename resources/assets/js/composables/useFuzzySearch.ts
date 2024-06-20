import Fuse from 'fuse.js'
import { isRef, Ref, watch } from 'vue'

type Path<T> = T extends object ? {
  [K in keyof T]:
  `${Exclude<K, symbol>}${"" | `.${Path<T[K]>}`}`
}[keyof T] : never

export const useFuzzySearch = <T> (items: T[] | Ref<T[]>, keys: Path<T>[] | string[]) => {
  const fuse = new Fuse<T>([], { keys })
  let documents = items

  const setDocuments = (newDocuments: T[]) => {
    documents = newDocuments
    fuse.setCollection(documents)
  }

  if (isRef<T[]>(items)) {
    fuse.setCollection(items.value)
    watch(items, () => setDocuments(items.value))
  } else {
    setDocuments(items)
  }

  const search = (query: string | null) => {
    query = query?.trim() ?? null

    return query ? fuse.search(query).map(result => result.item)
      : isRef(documents) ? documents.value : documents
  }

  return {
    search,
    setDocuments
  }
}
