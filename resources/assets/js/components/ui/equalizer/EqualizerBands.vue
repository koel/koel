<template>
  <div class="t-4 b-5 x-4 p-4 flex gap-1 rounded-md bg-black/20">
    <EqualizerBand ref="preampBandEl" v-model="preampGain" type="preamp" @commit="emit('commit')">Preamp</EqualizerBand>

    <span class="text-sm h-[100px] w-[20px] flex flex-col justify-between items-center opacity-50">
      <span class="leading-none text-k-fg">+20</span>
      <span class="leading-none text-k-fg">0</span>
      <span class="leading-none text-k-fg">-20</span>
    </span>

    <div ref="filterBandsEl" class="relative flex-1 flex justify-between">
      <div class="absolute left-0 right-0 top-[50px] h-px pointer-events-none baseline" />

      <EqualizerBand
        v-for="band in bands"
        :key="band.label"
        ref="filterBandEls"
        v-model="band.db"
        @commit="onBandCommit"
        @update:model-value="onBandChange(band)"
      >
        {{ band.label }}
      </EqualizerBand>

      <EqualizerCurve :points="curvePoints" />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import type { Band } from '@/services/audioService'
import { audioService } from '@/services/audioService'

import EqualizerBand from '@/components/ui/equalizer/EqualizerBand.vue'
import EqualizerCurve from '@/components/ui/equalizer/EqualizerCurve.vue'

defineProps<{ bands: Band[] }>()

const emit = defineEmits<{
  (e: 'user-change'): void
  (e: 'commit'): void
}>()

const preampGain = ref(0)
const preampBandEl = ref<InstanceType<typeof EqualizerBand>>()
const filterBandEls = ref<InstanceType<typeof EqualizerBand>[]>()
const filterBandsEl = ref<HTMLElement>()
const curvePoints = ref<{ x: number; y: number }[]>([])

let applyingPreset = false
let curveAnimationId = 0

const updateCurvePoints = () => {
  if (!filterBandEls.value?.length || !filterBandsEl.value) {
    return
  }

  const containerRect = filterBandsEl.value.getBoundingClientRect()

  curvePoints.value = filterBandEls.value.map(bandEl => {
    const el = bandEl.$el as HTMLElement
    const handle = el.querySelector<HTMLElement>('.noUi-handle')

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

const loadPreset = async (preset: EqualizerPreset, audioBands: Band[]) => {
  applyingPreset = true
  preampGain.value = preset.preamp
  preampBandEl.value?.updateSliderValue(preset.preamp)

  preset.gains.forEach((gain, index) => {
    audioBands[index].db = gain
    audioService.changeFilterGain(audioBands[index].node, gain)
    filterBandEls.value?.[index]?.updateSliderValue(gain)
  })

  await nextTick()
  applyingPreset = false
  animateCurveToHandles()
}

const getPreamp = () => preampGain.value

watch(preampGain, value => {
  audioService.changePreampGain(value)

  if (!applyingPreset) {
    emit('user-change')
  }
})

const onBandChange = (band: Band) => {
  audioService.changeFilterGain(band.node, band.db)

  if (!applyingPreset) {
    updateCurvePoints()
    emit('user-change')
  }
}

const onBandCommit = () => {
  emit('commit')
  animateCurveToHandles()
}

onBeforeUnmount(() => cancelAnimationFrame(curveAnimationId))

defineExpose({ loadPreset, getPreamp })
</script>

<style scoped>
.baseline {
  background: linear-gradient(
    to right,
    transparent,
    color-mix(in srgb, var(--color-fg) 10%, transparent) 5%,
    color-mix(in srgb, var(--color-fg) 10%, transparent) 95%,
    transparent
  );
}
</style>
