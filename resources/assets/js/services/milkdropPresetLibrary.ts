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

export const milkdropPresetLibrary = {
  async getNames() {
    return await (await getDB()).getAllKeys('presets')
  },

  async getContent(name: MilkdropPreset['name']) {
    return (await (await getDB()).get('presets', name))?.content
  },

  async remove(name: MilkdropPreset['name']) {
    await (await getDB()).delete('presets', name)
  },

  async importFiles(files: Iterable<File>) {
    const allFiles = [...files]
    const presetFiles = allFiles.filter(isMilkdropPresetFile)

    const presets = await Promise.all(
      presetFiles.map(async file => ({
        name: file.name.replace(/\.milk$/i, ''),
        content: await file.text(),
        importedAt: Date.now(),
      })),
    )

    const db = await getDB()
    await Promise.all(presets.map(preset => db.put('presets', preset)))

    return {
      importedNames: presets.map(({ name }) => name),
      skippedCount: allFiles.length - presetFiles.length,
    }
  },
}
