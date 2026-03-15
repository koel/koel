import type { DBSchema, IDBPDatabase } from 'idb'
import { openDB } from 'idb'

export interface OfflineManifestEntry {
  playable: Playable
  cachedAt: number
  size: number
}

/** The stored format includes an explicit key for IndexedDB */
interface StoredEntry extends OfflineManifestEntry {
  id: string
}

interface KoelOfflineDB extends DBSchema {
  manifest: {
    key: string
    value: StoredEntry
  }
}

const DB_NAME = 'koel-offline'
const DB_VERSION = 2

let dbInstance: IDBPDatabase<KoelOfflineDB> | null = null
let dbPromise: Promise<IDBPDatabase<KoelOfflineDB>> | null = null

const getDB = async () => {
  if (dbInstance) return dbInstance
  if (dbPromise) return dbPromise

  dbPromise = openDB<KoelOfflineDB>(DB_NAME, DB_VERSION, {
    upgrade(db) {
      // v1 used 'songId' as keyPath — drop and recreate with 'id'
      if (db.objectStoreNames.contains('manifest')) {
        db.deleteObjectStore('manifest')
      }

      db.createObjectStore('manifest', { keyPath: 'id' })
    },
  })

  try {
    dbInstance = await dbPromise
    return dbInstance
  } finally {
    dbPromise = null
  }
}

export const offlineManifest = {
  async getAll() {
    try {
      return await (await getDB()).getAll('manifest')
    } catch {
      return []
    }
  },

  async put(entry: OfflineManifestEntry) {
    try {
      await (await getDB()).put('manifest', { ...entry, id: entry.playable.id })
    } catch {
      // noop — indexedDB may not be available
    }
  },

  async remove(songId: Song['id']) {
    try {
      await (await getDB()).delete('manifest', songId)
    } catch {
      // noop
    }
  },

  async clear() {
    try {
      await (await getDB()).clear('manifest')
    } catch {
      // noop
    }
  },
}
