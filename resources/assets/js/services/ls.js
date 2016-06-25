import localStore from 'local-storage';

export const ls = {
  get(key, defaultVal = null) {
    const val = localStore(key);

    return val ? val : defaultVal;
  },

  set(key, val) {
    return localStore(key, val);
  },

  remove(key) {
    return localStore.remove(key);
  },
};
