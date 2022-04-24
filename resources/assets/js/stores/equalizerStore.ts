import { preferenceStore } from '.'

export const equalizerStore = {
  presets: [
    {
      id: 0,
      name: 'Default',
      preamp: 0,
      gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
    },
    {
      id: 1,
      name: 'Classical',
      preamp: -1,
      gains: [-1, -1, -1, -1, -1, -1, -7, -7, -7, -9]
    },
    {
      id: 2,
      name: 'Club',
      preamp: -6.7,
      gains: [-1, -1, 8, 5, 5, 5, 3, -1, -1, -1]
    },
    {
      id: 3,
      name: 'Dance',
      preamp: -4.3,
      gains: [9, 7, 2, -1, -1, -5, -7, -7, -1, -1]
    },
    {
      id: 4,
      name: 'Full Bass',
      preamp: -7.2,
      gains: [-8, 9, 9, 5, 1, -4, -8, -10, -11, -11]
    },
    {
      id: 5,
      name: 'Full Treble',
      preamp: -12,
      gains: [-9, -9, -9, -4, 2, 11, 16, 16, 16, 16]
    },
    {
      id: 6,
      name: 'Headphone',
      preamp: -8,
      gains: [4, 11, 5, -3, -2, 1, 4, 9, 12, 14]
    },
    {
      id: 7,
      name: 'Large Hall',
      preamp: -7.2,
      gains: [10, 10, 5, 5, -1, -4, -4, -4, -1, -1]
    },
    {
      id: 8,
      name: 'Live',
      preamp: -5.3,
      gains: [-4, -1, 4, 5, 5, 5, 4, 2, 2, 2]
    },
    {
      id: 9,
      name: 'Pop',
      preamp: -6.2,
      gains: [-1, 4, 7, 8, 5, -1, -2, -2, -1, -1]
    },
    {
      id: 10,
      name: 'Reggae',
      preamp: -8.2,
      gains: [-1, -1, -1, -5, -1, 6, 6, -1, -1, -1]
    },
    {
      id: 11,
      name: 'Rock',
      preamp: -10,
      gains: [8, 4, -5, -8, -3, 4, 8, 11, 11, 11]
    },
    {
      id: 12,
      name: 'Soft Rock',
      preamp: -5.3,
      gains: [4, 4, 2, -1, -4, -5, -3, -1, 2, 8]
    },
    {
      id: 13,
      name: 'Techno',
      preamp: -7.7,
      gains: [8, 5, -1, -5, -4, -1, 8, 9, 9, 8]
    }
  ],

  getPresetById (id: number): EqualizerPreset {
    return <EqualizerPreset> this.presets.find(preset => preset.id === id)
  },

  /**
   * Get the current equalizer config.
   */
  get (): EqualizerPreset {
    if (!this.presets[preferenceStore.selectedPreset]) {
      return preferenceStore.equalizer
    }

    // If the user chose a preset (instead of customizing one), just return it.
    return this.getPresetById(preferenceStore.selectedPreset)
  },

  /**
   * Save the current equalizer config.
   *
   * @param  {number} preamp The preamp value (dB)
   * @param  {number[]} gains  The band's gain value (dB)
   */
  set: (preamp: number, gains: number[]): void => {
    preferenceStore.equalizer = {
      id: -1,
      name: 'Custom',
      preamp,
      gains
    }
  }
}
