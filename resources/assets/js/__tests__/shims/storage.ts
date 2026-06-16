// jsdom under vite-plus 0.1.24 exposes localStorage / sessionStorage as plain
// empty objects without the Storage methods. Install a minimal in-memory shim
// that satisfies tests that round-trip values through these APIs.
const createStorage = (): Storage => {
  const store = new Map<string, string>()

  return {
    get length() {
      return store.size
    },
    clear: () => store.clear(),
    getItem: key => (store.has(key) ? store.get(key)! : null),
    setItem: (key, value) => {
      store.set(key, value)
    },
    removeItem: key => {
      store.delete(key)
    },
    key: index => Array.from(store.keys())[index] ?? null,
  }
}

Object.defineProperty(window, 'localStorage', { configurable: true, value: createStorage() })

Object.defineProperty(window, 'sessionStorage', { configurable: true, value: createStorage() })
