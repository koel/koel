import localStore from 'local-storage'

export const ls = {
  get: (key, defaultVal = null) => localStore(key) || defaultVal,
  set: (key, val) => localStore(key, val),
  remove: key => localStore.remove(key)
}
