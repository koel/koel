import { get as baseGet, set as baseSet, remove as baseRemove } from 'local-storage'

export const ls = {
  get: <T>(key: string, defaultValue: T | null = null): T | null => {
    const value = baseGet<T>(key)

    return value === null ? defaultValue : value
  },

  set: <T>(key: string, value: T): boolean => {
    return baseSet<T>(key, value)
  },

  remove: (key: string): void => {
    baseRemove(key)
  }
}
