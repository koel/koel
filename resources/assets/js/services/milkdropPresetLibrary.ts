import type { DBSchema, IDBPDatabase } from 'idb'
import { openDB } from 'idb'

export interface MilkdropPreset {
  name: string
  content: string
  importedAt: number
}

interface KoelMilkdropDB extends DBSchema {
  presets: {
    key: string
    value: MilkdropPreset
  }
}

let dbPromise: Promise<IDBPDatabase<KoelMilkdropDB>> | null = null

const getDB = () =>
  (dbPromise ??= openDB<KoelMilkdropDB>('koel-milkdrop', 1, {
    upgrade: db => db.createObjectStore('presets', { keyPath: 'name' }),
  }))

const isMilkdropPresetFile = (file: File) => file.name.toLowerCase().endsWith('.milk')

const importListeners = new Set<(presets: MilkdropPreset[]) => void>()

export const milkdropPresetLibrary = {
  async getAll() {
    return await (await getDB()).getAll('presets')
  },

  async importFiles(files: Iterable<File>) {
    const presets = await Promise.all(
      [...files].filter(isMilkdropPresetFile).map(async file => ({
        name: file.name.replace(/\.milk$/i, ''),
        content: await file.text(),
        importedAt: Date.now(),
      })),
    )

    const db = await getDB()
    await Promise.all(presets.map(preset => db.put('presets', preset)))

    presets.length && importListeners.forEach(listener => listener(presets))

    return presets
  },

  onImport(listener: (presets: MilkdropPreset[]) => void) {
    importListeners.add(listener)

    return () => importListeners.delete(listener)
  },
}
