import { preferenceStore as preferences } from '@/stores'
import { equalizerPresets as presets } from '@/config'

export const equalizerStore = {
  getPresetById (id: number) {
    return presets.find(preset => preset.id === id)
  },

  /**
   * Get the current equalizer config.
   */
  getConfig () {
    if (preferences.equalizer.id === -1) {
      return preferences.equalizer
    }

    // If the user chose a preset (instead of customizing one), just return it.
    return this.getPresetById(preferences.equalizer.id) || presets[0]
  },

  /**
   * Save the current equalizer config.
   */
  saveConfig (id: number, preamp: number, gains: number[]) {
    const preset = this.getPresetById(id)

    preferences.equalizer = preset || {
      preamp,
      gains,
      id: -1,
      name: 'Custom'
    }
  }
}
