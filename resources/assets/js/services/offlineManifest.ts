import { logger } from '@/utils/logger'

export interface OfflineManifestEntry {
  songId: string
  title: string
  artist: string
  album: string
  cachedAt: number
  size: number
}

const DB_NAME = 'koel-offline'
const DB_VERSION = 1
const STORE_NAME = 'manifest'

let dbPromise: Promise<IDBDatabase> | null = null

const openDB = (): Promise<IDBDatabase> => {
  if (dbPromise) return dbPromise

  if (typeof indexedDB === 'undefined') {
    return Promise.reject(new Error('indexedDB is not available'))
  }

  dbPromise = new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION)

    request.onupgradeneeded = () => {
      const db = request.result
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        db.createObjectStore(STORE_NAME, { keyPath: 'songId' })
      }
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
  async getAll(): Promise<OfflineManifestEntry[]> {
    try {
      return await withStore('readonly', store => store.getAll())
    } catch (error) {
      logger.warn('Failed to read offline manifest', error)
      return []
    }
  },

  async get(songId: string): Promise<OfflineManifestEntry | undefined> {
    try {
      return await withStore('readonly', store => store.get(songId))
    } catch (error) {
      logger.warn('Failed to read offline manifest entry', error)
      return undefined
    }
  },

  async put(entry: OfflineManifestEntry): Promise<void> {
    try {
      await withStore('readwrite', store => store.put(entry))
    } catch (error) {
      logger.warn('Failed to write offline manifest entry', error)
    }
  },

  async remove(songId: string): Promise<void> {
    try {
      await withStore('readwrite', store => store.delete(songId))
    } catch (error) {
      logger.warn('Failed to remove offline manifest entry', error)
    }
  },

  async clear(): Promise<void> {
    try {
      await withStore('readwrite', store => store.clear())
    } catch (error) {
      logger.warn('Failed to clear offline manifest', error)
    }
  },
}
