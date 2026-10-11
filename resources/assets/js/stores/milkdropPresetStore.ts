import { reactive } from 'vue'
import { milkdropPresetLibrary } from '@/services/milkdropPresetLibrary'
import { useLocalStorage } from '@/composables/useLocalStorage'

export const RANDOM_PRESET = ''

const SELECTED_PRESET_STORAGE_KEY = 'milkdrop-preset'

const { get: lsGet, set: lsSet } = useLocalStorage(false)

export const milkdropPresetStore = {
  state: reactive({
    names: [] as string[],
    selected: lsGet<string>(SELECTED_PRESET_STORAGE_KEY) ?? RANDOM_PRESET,
  }),

  async load() {
    this.state.names = await milkdropPresetLibrary.getNames()
  },

  async importFiles(files: Iterable<File>) {
    const result = await milkdropPresetLibrary.importFiles(files)

    await this.load()

    if (this.state.selected !== RANDOM_PRESET && result.importedNames.length) {
      this.select(result.importedNames[0])
    }

    return result
  },

  async remove(name: string) {
    await milkdropPresetLibrary.remove(name)
    await this.load()

    if (this.state.selected === name) {
      this.select(RANDOM_PRESET)
    }
  },

  select(name: string) {
    this.state.selected = name
    lsSet(SELECTED_PRESET_STORAGE_KEY, name)
  },
}
