import { useAuthorization } from '@/composables/useAuthorization'

const baseGet = <T>(key: string): T | null => {
  const raw = localStorage.getItem(key)

  if (raw === null) {
    return null
  }

  try {
    return JSON.parse(raw) as T
  } catch {
    return null
  }
}

const baseSet = (key: string, value: unknown) => localStorage.setItem(key, JSON.stringify(value))
const baseRemove = (key: string) => localStorage.removeItem(key)

export const useLocalStorage = (namespaced = true, user?: User) => {
  let namespace = ''

  if (namespaced) {
    namespace = user ? `${user.id}::` : `${useAuthorization().currentUser.value.id}::`
  }

  const get = <T>(key: string, defaultValue: T | null = null): T | null => {
    const value = baseGet<T>(namespace + key)

    return value === null ? defaultValue : value
  }

  const set = (key: string, value: any) => baseSet(namespace + key, value)
  const remove = (key: string) => baseRemove(namespace + key)

  return {
    get,
    set,
    remove,
  }
}
