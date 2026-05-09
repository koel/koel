import { reactive } from 'vue'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { equalizerPresets as builtInPresets } from '@/config/audio'
import { ulid } from '@/utils/crypto'

const state = reactive({
  customPresets: [] as EqualizerPreset[],
})

const persist = () => {
  preferences.equalizer_presets = [...state.customPresets]
}

export const equalizerStore = {
  state,

  init() {
    state.customPresets = preferences.equalizer_presets ?? []
  },

  isBuiltIn: (preset: EqualizerPreset) =>
    preset.id === undefined && preset.name !== null && builtInPresets.some(p => p.name === preset.name),

  isCustom: (preset: EqualizerPreset) => preset.id !== undefined,

  isModified(preset: any) {
    return (
      typeof preset === 'object' &&
      preset !== null &&
      preset.id === undefined &&
      preset.name === null &&
      typeof preset.preamp === 'number' &&
      Array.isArray(preset.gains) &&
      preset.gains.length === 10 &&
      preset.gains.every((gain: any) => typeof gain === 'number')
    )
  },

  getBuiltInPresetByName: (name: string) => builtInPresets.find(p => p.name === name),

  getCustomPresetById: (id: string) => state.customPresets.find(p => p.id === id),

  /**
   * Resolve the preset to apply on app load.
   */
  getConfig(): EqualizerPreset {
    const current = preferences.current_equalizer_preset

    if (current.id) {
      // Saved-custom: resolve by id. If the saved preset was deleted elsewhere,
      // keep the user's slider state by demoting to a modified preset.
      return this.getCustomPresetById(current.id) ?? { name: null, preamp: current.preamp, gains: current.gains }
    }

    if (current.name !== null) {
      return this.getBuiltInPresetByName(current.name) ?? builtInPresets[0]
    }

    return current
  },

  /**
   * Persist the user's last-applied preset.
   */
  saveConfig(preset: EqualizerPreset | null, preamp: number, gains: number[]) {
    preferences.current_equalizer_preset = preset ?? { name: null, preamp, gains }
  },

  saveCustomPreset(name: string, preamp: number, gains: number[]): EqualizerPreset {
    const preset: EqualizerPreset = { id: ulid(), name, preamp, gains: [...gains] }
    state.customPresets.push(preset)
    persist()

    return preset
  },

  deleteCustomPreset(id: string) {
    state.customPresets = state.customPresets.filter(p => p.id !== id)
    persist()
  },
}
