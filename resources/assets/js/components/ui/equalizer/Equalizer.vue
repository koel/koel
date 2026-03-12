<template>
  <div class="select-none w-full flex flex-col" tabindex="0" @keydown.esc="close">
    <header>
      <SelectBox v-model="selectedPresetName" class="!bg-black/30 !text-white" title="Select equalizer">
        <option :value="null" disabled>Preset</option>
        <option v-for="preset in presets" :key="preset.name!" :value="preset.name">{{ preset.name }}</option>
      </SelectBox>
    </header>

    <main>
      <div class="t-4 b-5 x-4 p-4 flex gap-1 rounded-md bg-black/20">
        <EqualizerBand ref="preampBandEl" v-model="preampGain" type="preamp" @commit="save">Preamp</EqualizerBand>

        <span class="text-sm h-[100px] w-[20px] flex flex-col justify-between items-center opacity-50">
          <span class="leading-none text-k-fg">+20</span>
          <span class="leading-none text-k-fg">0</span>
          <span class="leading-none text-k-fg">-20</span>
        </span>

        <div ref="filterBandsEl" class="relative flex-1 flex justify-between">
          <div
            class="absolute left-0 right-0 top-[50px] h-px pointer-events-none"
            style="
              background: linear-gradient(
                to right,
                transparent,
                color-mix(in srgb, var(--color-fg) 10%, transparent) 5%,
                color-mix(in srgb, var(--color-fg) 10%, transparent) 95%,
                transparent
              );
            "
          />
          <EqualizerBand
            v-for="band in bands"
            :key="band.label"
            ref="filterBandEls"
            v-model="band.db"
            @commit="commitFilterGain"
            @update:model-value="changeFilterGain(band)"
          >
            {{ band.label }}
          </EqualizerBand>

          <EqualizerCurve :points="curvePoints" />
        </div>
      </div>
    </main>

    <footer class="border-t-k-fg-5">
      <Btn @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { equalizerStore } from '@/stores/equalizerStore'
import type { Band } from '@/services/audioService'
import { audioService } from '@/services/audioService'
import { equalizerPresets as presets } from '@/config/audio'

import Btn from '@/components/ui/form/Btn.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import EqualizerBand from '@/components/ui/equalizer/EqualizerBand.vue'
import EqualizerCurve from '@/components/ui/equalizer/EqualizerCurve.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const bands = audioService.bands
const preampGain = ref(0)
const selectedPresetName = ref<EqualizerPreset['name']>(null)
const preampBandEl = ref<InstanceType<typeof EqualizerBand>>()
const filterBandEls = ref<InstanceType<typeof EqualizerBand>[]>()
const filterBandsEl = ref<HTMLElement>()
const curvePoints = ref<{ x: number; y: number }[]>([])

let curveAnimationId = 0

const updateCurvePoints = () => {
  if (!filterBandEls.value?.length || !filterBandsEl.value) {
    return
  }

  const containerRect = filterBandsEl.value.getBoundingClientRect()

  curvePoints.value = filterBandEls.value.map(bandEl => {
    const el = bandEl.$el as HTMLElement
    const handle = el.querySelector('.noUi-handle') as HTMLElement

    if (!handle) {
      return { x: 0, y: 0 }
    }

    const handleRect = handle.getBoundingClientRect()
    const x = handleRect.left - containerRect.left + handleRect.width / 2
    const y = handleRect.top - containerRect.top + handleRect.height / 2

    return { x, y }
  })
}

/**
 * Continuously read handle positions over the duration of the noUi-state-tap
 * CSS transition (~300ms) so the curve animates smoothly alongside the handles.
 */
const animateCurveToHandles = () => {
  cancelAnimationFrame(curveAnimationId)

  const start = performance.now()
  const duration = 350

  const tick = () => {
    updateCurvePoints()

    if (performance.now() - start < duration) {
      curveAnimationId = requestAnimationFrame(tick)
    }
  }

  curveAnimationId = requestAnimationFrame(tick)
}

// A flag to determine if the changes made to the bands are from loading a preset
// or by user customizing the sliders, in such a case the preset name should
// be set to null (customized).
let applyingPreset = false

const loadPreset = async (preset: EqualizerPreset) => {
  applyingPreset = true
  preampGain.value = preset.preamp
  preampBandEl.value?.updateSliderValue(preset.preamp)

  preset.gains.forEach((gain, i) => {
    bands[i].db = gain
    audioService.changeFilterGain(bands[i].node, gain)
    filterBandEls.value![i].updateSliderValue(gain)
  })

  await nextTick()
  applyingPreset = false
  animateCurveToHandles()
}

const save = () =>
  equalizerStore.saveConfig(
    selectedPresetName.value,
    preampGain.value,
    bands.map(band => band.db),
  )
const close = () => emit('close')

watch(preampGain, value => {
  audioService.changePreampGain(value)
  if (!applyingPreset) {
    selectedPresetName.value = null
  }
})

const changeFilterGain = (band: Band) => {
  audioService.changeFilterGain(band.node, band.db)

  if (!applyingPreset) {
    updateCurvePoints()
    selectedPresetName.value = null
  }
}

const commitFilterGain = () => {
  save()
  animateCurveToHandles()
}

watch(selectedPresetName, value => {
  if (value !== null) {
    loadPreset(equalizerStore.getPresetByName(value) || presets[0])
  }

  save()
})

onMounted(async () => {
  const { name, preamp } = equalizerStore.getConfig()
  selectedPresetName.value = name
  preampGain.value = preamp
  await nextTick()
  requestAnimationFrame(updateCurvePoints)
})

onBeforeUnmount(() => cancelAnimationFrame(curveAnimationId))
</script>
