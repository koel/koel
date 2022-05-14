import { get as baseGet, remove as baseRemove, set as baseSet } from 'local-storage'

export const localStorageService = {
  get: <T> (key: string, defaultValue: T | null = null): T | null => {
    const value = baseGet<T>(key)

    return value === null ? defaultValue : value
  },

  set: (key: string, value: any) => baseSet(key, value),
  remove: (key: string) => baseRemove(key)
}
