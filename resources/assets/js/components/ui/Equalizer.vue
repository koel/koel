<template>
  <form id="equalizer" ref="root" data-testid="equalizer" tabindex="0" @keydown.esc="close">
    <header>
      <label class="select-wrapper">
        <select v-model="selectedPresetId" title="Select equalizer">
          <option disabled value="-1">Preset</option>
          <option v-for="preset in presets" :key="preset.id" :value="preset.id">{{ preset.name }}</option>
        </select>
        <icon :icon="faCaretDown" class="arrow text-highlight" size="sm" />
      </label>
    </header>

    <main>
      <div class="bands">
        <span class="band">
          <span class="slider" />
          <label>Preamp</label>
        </span>

        <span class="indicators">
          <span>+20</span>
          <span>0</span>
          <span>-20</span>
        </span>

        <span v-for="band in bands" :key="band.label" class="band">
          <span class="slider" />
          <label>{{ band.label }}</label>
        </span>
      </div>
    </main>

    <footer>
      <Btn @click.prevent="close">Close</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import noUiSlider from 'nouislider'
import { faCaretDown } from '@fortawesome/free-solid-svg-icons'
import { onMounted, ref, watch } from 'vue'
import { equalizerStore } from '@/stores'
import { audioService } from '@/services'
import { equalizerPresets as presets } from '@/config'

import Btn from '@/components/ui/Btn.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const bands = audioService.bands
const root = ref<HTMLElement>()
const preampGain = ref(0)
const selectedPresetId = ref(-1)

watch(preampGain, value => audioService.changePreampGain(value))

watch(selectedPresetId, () => {
  if (selectedPresetId.value !== -1) {
    loadPreset(equalizerStore.getPresetById(selectedPresetId.value) || presets[0])
  }

  save()
})

const createSliders = () => {
  const config = equalizerStore.getConfig()

  selectedPresetId.value = config.id
  preampGain.value = config.preamp

  if (!root.value) {
    throw new Error('Equalizer config or root element not found')
  }

  root.value.querySelectorAll<EqualizerBandElement>('.slider').forEach((el, i) => {
    noUiSlider.create(el, {
      connect: [false, true],
      // the first element is the preamp. The rest are gains.
      start: i === 0 ? config.preamp : config.gains[i - 1],
      range: { min: -20, max: 20 },
      orientation: 'vertical',
      direction: 'rtl'
    })

    el.isPreamp = i === 0

    el.noUiSlider.on('slide', (values, handle) => {
      const value = parseFloat(values[handle])

      if (el.isPreamp) {
        preampGain.value = value
      } else {
        audioService.changeFilterGain(bands[i - 1].filter, value)
      }

      // User has customized the equalizer. No preset should be selected.
      selectedPresetId.value = -1

      save()
    })
  })
}

const loadPreset = (preset: EqualizerPreset) => {
  preampGain.value = preset.preamp

  root.value?.querySelectorAll<EqualizerBandElement>('.slider').forEach((el, i) => {
    if (!el.noUiSlider) {
      throw new Error('Preset can only be loaded after sliders have been set up')
    }

    if (el.isPreamp) {
      el.noUiSlider.set(preset.preamp)
    } else {
      audioService.changeFilterGain(bands[i - 1].filter, preset.gains[i - 1])
      el.noUiSlider.set(preset.gains[i - 1])
    }
  })
}

const save = () => equalizerStore.saveConfig(selectedPresetId.value, preampGain.value, bands.map(band => band.db))
const close = () => emit('close')

onMounted(() => createSliders())
</script>

<style lang="scss">
#equalizer {
  user-select: none;
  width: 100%;
  display: flex;
  flex-direction: column;

  footer {
    border-top: 1px solid rgba(255, 255, 255, .05);
  }

  label {
    margin-top: 8px;
    margin-bottom: 0;
    text-align: left;
  }

  header {
    padding: 12px 16px;

    .select-wrapper {
      margin-top: 0;
      position: relative;
      padding: 0 8px;
      background: rgba(0, 0, 0, .2);
      border-radius: 5px;

      .arrow {
        margin-left: -6px;
        pointer-events: none;
      }
    }

    select {
      background: none;
      color: var(--color-text-primary);
      padding-left: 0;
      width: 100px;
      text-transform: none;

      option {
        color: var(--color-black);
      }
    }
  }

  .bands {
    padding: 14px 16px 12px;
    border-radius: 4px;
    background: rgba(0, 0, 0, .2);
    display: flex;
    justify-content: space-between;

    label, .indicators {
      font-size: .8rem;
    }

    .band {
      display: flex;
      flex-direction: column;
      align-items: center;
      min-width: 24px;
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
      opacity: .5;

      span:first-child {
        line-height: 8px;
      }

      span:last-child {
        line-height: 8px;
      }
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
        top: 0;
        left: 7px;
      }
    }

    &-touch-area {
      cursor: ns-resize;
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
        background: linear-gradient(to bottom, var(--color-highlight) 0%, var(--color-highlight) 36%, var(--color-green) 100%);
        background-size: 2px;
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
        height: 6px;
        left: -16px;
        border-radius: 9999px;
        top: 0;
      }
    }
  }
}
</style>
