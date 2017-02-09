<template>
  <div id="equalizer">
    <div class="presets">
      <label class="select-wrapper">
        <select v-model="selectedPresetIndex">
          <option v-for="p in presets" :value="p.id" v-once>{{ p.name }}</option>
        </select>
      </label>
    </div>
    <div class="bands">
      <span class="band preamp">
        <span class="slider"></span>
        <label>Preamp</label>
      </span>

      <span class="indicators">
        <span>+20</span>
        <span>0</span>
        <span>-20</span>
      </span>

      <span class="band amp" v-for="band in bands">
        <span class="slider"></span>
        <label>{{ band.label }}</label>
      </span>
    </div>
  </div>
</template>

<script>
import { map, cloneDeep, each } from 'lodash'
import nouislider from 'nouislider'

import { isAudioContextSupported, event, $ } from '../../utils'
import { equalizerStore, preferenceStore as preferences } from '../../stores'

export default {
  data () {
    return {
      bands: [],
      preampGainValue: 0,
      selectedPresetIndex: -1
    }
  },

  computed: {
    presets () {
      const clonedPreset = cloneDeep(equalizerStore.presets)
      // Prepend an empty option for instruction purpose.
      clonedPreset.unshift({
        id: -1,
        name: 'Preset'
      })
      return clonedPreset
    }
  },

  watch: {
    /**
     * Watch selectedPresetIndex and trigger our logic.
     * @param {Number} val
     */
    selectedPresetIndex (val) {
      /**
       * Save the selected preset (index) into local storage every time the value's changed.
       */
      preferences.selectedPreset = val

      if (~~val !== -1) {
        this.loadPreset(equalizerStore.getPresetById(val))
      }
    }
  },

  methods: {
    /**
     * Init the equalizer.
     * @param  {Element} player The audio player's node.
     */
    init (player) {
      const settings = equalizerStore.get()

      const AudioContext = window.AudioContext ||
        window.webkitAudioContext ||
        window.mozAudioContext ||
        window.oAudioContext ||
        window.msAudioContext

      const context = new AudioContext()

      this.preampGainNode = context.createGain()
      this.changePreampGain(settings.preamp)

      const source = context.createMediaElementSource(player)
      source.connect(this.preampGainNode)

      let prevFilter = null

      // Create 10 bands with the frequencies similar to those of Winamp and connect them together.
      const frequencies = [60, 170, 310, 600, 1000, 3000, 6000, 12000, 14000, 16000]
      each(frequencies, (frequency, i) => {
        const filter = context.createBiquadFilter()

        if (i === 0) {
          filter.type = 'lowshelf'
        } else if (i === 9) {
          filter.type = 'highshelf'
        } else {
          filter.type = 'peaking'
        }

        filter.gain.value = settings.gains[i] ? settings.gains[i] : 0
        filter.Q.value = 1
        filter.frequency.value = frequency

        prevFilter ? prevFilter.connect(filter) : this.preampGainNode.connect(filter)
        prevFilter = filter

        this.bands.push({
          filter,
          label: (frequency + '').replace('000', 'K')
        })
      })

      prevFilter.connect(context.destination)

      this.$nextTick(this.createSliders)
    },

    /**
     * Create the UI sliders for both the preamp and the normal bands.
     */
    createSliders () {
      const config = equalizerStore.get()
      each(Array.from(document.querySelectorAll('#equalizer .slider')), (el, i) => {
        nouislider.create(el, {
          connect: [false, true],
          // the first element is the preamp. The rest are gains.
          start: i === 0 ? config.preamp : config.gains[i - 1],
          range: { min: -20, max: 20 },
          orientation: 'vertical',
          direction: 'rtl'
        })

        /**
         * Update the audio effect upon sliding / tapping.
         */
        el.noUiSlider.on('slide', (values, handle) => {
          const value = values[handle]
          if (el.parentNode.matches('.preamp')) {
            this.changePreampGain(value)
          } else {
            this.changeFilterGain(this.bands[i - 1].filter, value)
          }
        })

        /**
         * Save the equalizer values after the change is done.
         */
        el.noUiSlider.on('change', () => {
          // User has customized the equalizer. No preset should be selected.
          this.selectedPresetIndex = -1
          this.save()
        })
      })

      // Now we set this value to trigger the audio processing.
      this.selectedPresetIndex = preferences.selectedPreset
    },

    /**
     * Change the gain value for the preamp.
     *
     * @param  {Number} dbValue The value of the gain, in dB.
     */
    changePreampGain (dbValue) {
      this.preampGainValue = dbValue
      this.preampGainNode.gain.value = Math.pow(10, dbValue / 20)
    },

    /**
     * Change the gain value for a band/filter.
     *
     * @param  {Object} filter The filter object
     * @param  {Object} value  Value of the gain, in dB.
     */
    changeFilterGain (filter, value) {
      filter.gain.value = value
    },

    /**
     * Load a preset when the user select it from the dropdown.
     */
    loadPreset (preset) {
      each(Array.from(document.querySelectorAll('#equalizer .slider')), (el, i) => {
        // We treat our preamp slider differently.
        if ($.is(el.parentNode, '.preamp')) {
          this.changePreampGain(preset.preamp)
          // Update the slider values into GUI.
          el.noUiSlider.set(preset.preamp)
        } else {
          this.changeFilterGain(this.bands[i - 1].filter, preset.gains[i - 1])
          // Update the slider values into GUI.
          el.noUiSlider.set(preset.gains[i - 1])
        }
      })

      this.save()
    },

    /**
     * Save the current user's equalizer preferences into local storage.
     */
    save () {
      equalizerStore.set(this.preampGainValue, map(this.bands, 'filter.gain.value'))
    }
  },

  mounted () {
    event.on('equalizer:init', player => {
      isAudioContextSupported() && this.init(player)
    })
  }
}
</script>

<style lang="sass">
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

#equalizer {
  user-select: none;
  position: absolute;
  bottom: $footerHeight;
  width: 100%;
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  flex-direction: column;
  left: 0;

  label {
    margin-top: 8px;
    margin-bottom: 0;
    text-align: left;
  }

  .presets {
    padding: 8px 16px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    flex: 1;
    align-content: center;
    z-index: 1;
    border-bottom: 1px solid rgba(255, 255, 255, .1);


    .select-wrapper {
      position: relative;
      margin-bottom: 0;

      &::after {
        content: '\f107';
        font-family: FontAwesome;
        color: $colorHighlight;
        display: inline-block;
        position: absolute;
        right: 8px;
        top: 3px;
        pointer-events: none;
      }
    }

    select {
      background: none;
      color: $colorMainText;
      padding-left: 0;
      width: 100px;
      text-transform: none;

      option {
        color: #333;
      }
    }
  }

  .bands {
    padding: 16px;
    z-index: 1;
    left: 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;

    label, .indicators {
      font-size: .8rem;
    }

    .band {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .slider {
      height: 100px;
    }

    .indicators {
      height: 100px;
      width: 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
      margin-left: -16px;
      opacity: 0;
      transition: .4s;

      span:first-child {
        line-height: 8px;
      }

      span:last-child {
        line-height: 8px;
      }
    }

    &:hover .indicators {
      opacity: 1;
    }
  }

  .noUi {
    &-connect {
      background: none;
      box-shadow: none;
      &::after {
        content: " ";
        position: absolute;
        width: 2px;
        height: 100%;
        background: #333;
        top: 0;
        left: 7px;
      }
    }

    &-target {
      background: transparent;
      border-radius: 0;
      border: 0;
      box-shadow: none;
      width: 16px;

      &::after {
        content: " ";
        position: absolute;
        width: 2px;
        height: 100%;
        background: #fff;
        top: 0;
        left: 7px;
      }
    }

    &-handle {
      border: 0;
      border-radius: 0;
      box-shadow: none;
      cursor: pointer;

      &::before, &::after {
        display: none;
      }
    }

    &-vertical {
      .noUi-handle {
        width: 16px;
        height: 2px;
        left: 0;
        top: 0;
      }
    }
  }

  @media only screen and (max-width : 768px) {
    position: fixed;
    max-width: 414px;
    left: auto;
    right: 0;
    bottom: $footerHeightMobile + 14px;
    display: block;
    height: auto;

    label {
      line-height: 20px;
    }
  }
}
</style>
