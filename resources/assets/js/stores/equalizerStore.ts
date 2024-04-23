import { preferenceStore as preferences } from '@/stores'
import { equalizerPresets as presets } from '@/config'

export const equalizerStore = {
  getPresetByName (name: string) {
    return presets.find(preset => preset.name === name)
  },

  /**
   * Get the current equalizer config.
   */
  getConfig () {
    let config: EqualizerPreset | undefined

    if (this.isCustom(preferences.equalizer)) return preferences.equalizer

    if (preferences.equalizer.name !== null) {
      config = this.getPresetByName(preferences.equalizer.name)
    }

    return config || presets[0]
  },

  isCustom (preset: any) {
    return typeof preset === 'object'
      && preset !== null
      && preset.name === null
      && typeof preset.preamp === 'number'
      && Array.isArray(preset.gains)
      && preset.gains.length === 10
      && preset.gains.every((gain: any) => typeof gain === 'number')
  },

  /**
   * Save the current equalizer config.
   */
  saveConfig (name: EqualizerPreset['name'] | null, preamp: number, gains: number[]) {
    const preset = name ? this.getPresetByName(name) : null

    preferences.equalizer = preset || {
      preamp,
      gains,
      name: null
    }
  }
}
