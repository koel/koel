import preferences from './preference';

export default {
    presets: [
        {
            name: 'Default',
            preamp: 0,
            gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        },
        {
            name: 'Classical',
            preamp: -1,
            gains: [-1, -1, -1, -1, -1, -1, -7, -7, -7, -9],
        },
        {
            name: 'Club',
            preamp: -6.7,
            gains: [-1, -1, 8, 5, 5, 5, 3, -1, -1, -1],
        },
        {
            name: 'Dance',
            preamp: -4.3,
            gains: [9, 7, 2, -1, -1, -5, -7, -7, -1, -1],
        },
        {
            name: 'Full Bass',
            preamp: -7.2,
            gains: [-8, 9, 9, 5, 1, -4, -8, -10, -11, -11]
        },
        {
            name: 'Full Treble',
            preamp: -12,
            gains: [-9, -9, -9, -4, 2, 11, 16, 16, 16, 16]
        },
        {
            name: 'Headphone',
            preamp: -8,
            gains: [4, 11, 5, -3, -2, 1, 4, 9, 12, 14]
        },
        {
            name: 'Large Hall',
            preamp: -7.2,
            gains: [10, 10, 5, 5, -1, -4, -4, -4, -1, -1],
        },
        {
            name: 'Live',
            preamp: -5.3,
            gains: [-4, -1, 4, 5, 5, 5, 4, 2, 2, 2],
        },
        {
            name: 'Pop',
            preamp: -6.2,
            gains: [-1, 4, 7, 8, 5, -1, -2, -2, -1, -1],
        },
        {
            name: 'Reggae',
            preamp: -8.2,
            gains: [-1, -1, -1, -5, -1, 6, 6, -1, -1, -1],
        },
        {
            name: 'Rock',
            preamp: -10,
            gains: [8, 4, -5, -8, -3, 4, 8, 11, 11, 11],
        },
        {
            name: 'Soft Rock',
            preamp: -5.3,
            gains: [4, 4, 2, -1, -4, -5, -3, -1, 2, 8],
        },
        {
            name: 'Techno',
            preamp: -7.7,
            gains: [8, 5, -1, -5, -4, -1, 8, 9, 9, 8],
        },
    ],

    /**
     * Get the current equalizer config.
     *
     * @return {Object}
     */
    get() {
        if (!this.presets[preferences.selectedPreset]) {
            return preferences.equalizer;
        }

        // If the user chose a preset (instead of customizing one), just return it.
        return this.presets[preferences.selectedPreset];
    },

    /**
     * Save the current equalizer config.
     *
     * @param  {Number} preamp The preamp value (dB)
     * @param  {Array.<Number>} gains  The band's gain value (dB)
     */
    set(preamp, gains) {
        preferences.equalizer = { preamp, gains };
    },
};
