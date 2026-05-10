import { reactive } from 'vue'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { equalizerPresets as builtInPresets } from '@/config/audio'
import { http } from '@/services/http'

const state = reactive({
  customPresets: [] as EqualizerPreset[],
})

const byName = (a: EqualizerPreset, b: EqualizerPreset) =>
  (a.name ?? '').localeCompare(b.name ?? '', undefined, { sensitivity: 'base' })

export const equalizerStore = {
  state,

  init() {
    state.customPresets = [...(preferences.equalizer_presets ?? [])].sort(byName)
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
      return this.getCustomPresetById(current.id) ?? { name: null, preamp: current.preamp, gains: [...current.gains] }
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

  async saveCustomPreset(name: string, preamp: number, gains: number[]): Promise<EqualizerPreset> {
    const preset = await http.post<EqualizerPreset>('me/equalizer-presets', { name, preamp, gains })
    state.customPresets = [...state.customPresets, preset].sort(byName)

    return preset
  },

  async deleteCustomPreset(id: string) {
    await http.delete(`me/equalizer-presets/${id}`)
    state.customPresets = state.customPresets.filter(p => p.id !== id)
  },
}
