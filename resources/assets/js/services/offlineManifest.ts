export interface OfflineManifestEntry {
  playable: Playable
  cachedAt: number
  size: number
}

/** The stored format includes an explicit key for IndexedDB */
interface StoredEntry extends OfflineManifestEntry {
  id: string
}

const DB_NAME = 'koel-offline'
const DB_VERSION = 2
const STORE_NAME = 'manifest'

let dbPromise: Promise<IDBDatabase> | null = null

const openDB = () => {
  if (dbPromise) return dbPromise

  if (typeof indexedDB === 'undefined') {
    return Promise.reject(new Error('indexedDB is not available'))
  }

  dbPromise = new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION)

    request.onupgradeneeded = () => {
      const db = request.result

      // v1 used 'songId' as keyPath — drop and recreate with 'id'
      if (db.objectStoreNames.contains(STORE_NAME)) {
        db.deleteObjectStore(STORE_NAME)
      }

      db.createObjectStore(STORE_NAME, { keyPath: 'id' })
    }

    request.onsuccess = () => resolve(request.result)
    request.onerror = () => {
      dbPromise = null
      reject(request.error)
    }
  })

  return dbPromise
}

const withStore = async <T>(mode: IDBTransactionMode, fn: (store: IDBObjectStore) => IDBRequest<T>): Promise<T> => {
  const db = await openDB()
  return new Promise((resolve, reject) => {
    const tx = db.transaction(STORE_NAME, mode)
    const store = tx.objectStore(STORE_NAME)
    const request = fn(store)
    request.onsuccess = () => resolve(request.result)
    request.onerror = () => reject(request.error)
  })
}

export const offlineManifest = {
  async getAll() {
    try {
      return await withStore('readonly', store => store.getAll())
    } catch {
      return []
    }
  },

  async put(entry: OfflineManifestEntry) {
    try {
      const stored: StoredEntry = { ...entry, id: entry.playable.id }
      await withStore('readwrite', store => store.put(stored))
    } catch {
      // noop — indexedDB may not be available
    }
  },

  async remove(songId: Song['id']) {
    try {
      await withStore('readwrite', store => store.delete(songId))
    } catch {
      // noop
    }
  },

  async clear() {
    try {
      await withStore('readwrite', store => store.clear())
    } catch {
      // noop
    }
  },
}
