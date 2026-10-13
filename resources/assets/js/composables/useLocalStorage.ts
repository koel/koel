import { useAuthorization } from '@/composables/useAuthorization'

const baseGet = <T>(key: string): T | null => {
  try {
    const raw = localStorage.getItem(key)

    return raw === null ? null : (JSON.parse(raw) as T)
  } catch {
    return null
  }
}

const baseSet = (key: string, value: unknown) => {
  const serialized = JSON.stringify(value)

  try {
    localStorage.setItem(key, serialized)
  } catch {}
}

const baseRemove = (key: string) => {
  try {
    localStorage.removeItem(key)
  } catch {}
}

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
