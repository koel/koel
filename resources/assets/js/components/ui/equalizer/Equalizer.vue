<template>
  <div class="select-none w-full flex flex-col" tabindex="0" @keydown.esc="close">
    <header>
      <SelectBox v-model="selectedPresetName" title="Select equalizer" class="!bg-black/30 !text-white">
        <option disabled :value="null">Preset</option>
        <option v-for="preset in presets" :key="preset.name!" :value="preset.name">{{ preset.name }}</option>
      </SelectBox>
    </header>

    <main>
      <div class="t-4 b-5 x-4 p-4 flex justify-between rounded-md bg-black/20">
        <EqualizerBand ref="preampBandEl" v-model="preampGain" type="preamp" @commit="save">Preamp</EqualizerBand>

        <span
          class="text-sm h-[100px] w-[20px] flex flex-col justify-between items-center -ml-6 opacity-50"
        >
          <span class="leading-none text-k-text-primary">+20</span>
          <span class="leading-none text-k-text-primary">0</span>
          <span class="leading-none text-k-text-primary">-20</span>
        </span>

        <EqualizerBand
          v-for="band in bands"
          ref="filterBandEls"
          :key="band.label"
          v-model="band.db"
          @commit="save"
          @update:model-value="changeFilterGain(band)"
        >
          {{ band.label }}
        </EqualizerBand>
      </div>
    </main>

    <footer class="border-t-white/5">
      <Btn @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { nextTick, onMounted, ref, watch } from 'vue'
import { equalizerStore } from '@/stores'
import { audioService, Band } from '@/services'
import { equalizerPresets as presets } from '@/config'

import Btn from '@/components/ui/form/Btn.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import EqualizerBand from '@/components/ui/equalizer/EqualizerBand.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const bands = audioService.bands
const preampGain = ref(0)
const selectedPresetName = ref<string | null>(null)
const preampBandEl = ref<InstanceType<typeof EqualizerBand>>()
const filterBandEls = ref<InstanceType<typeof EqualizerBand>[]>()

// A flag to determine if the changes made to the bands are from loading a preset
// or by user customizing the sliders, in such a case the preset name should
// be set to null (customized).
let applyingPreset = false

watch(preampGain, value => {
  audioService.changePreampGain(value)
  if (!applyingPreset) selectedPresetName.value = null
})

const changeFilterGain = (band: Band) => {
  audioService.changeFilterGain(band.node, band.db)
  if (!applyingPreset) selectedPresetName.value = null
}

watch(selectedPresetName, value => {
  if (value !== null) {
    loadPreset(equalizerStore.getPresetByName(value) || presets[0])
  }

  save()
})

const loadPreset = async (preset: EqualizerPreset) => {
  applyingPreset = true
  preampGain.value = preset.preamp
  preampBandEl.value.updateSliderValue(preset.preamp)

  preset.gains.forEach((gain, i) => {
    bands[i].db = gain
    audioService.changeFilterGain(bands[i].node, gain)
    filterBandEls.value![i].updateSliderValue(gain)
  })

  await nextTick()
  applyingPreset = false
}

const save = () => equalizerStore.saveConfig(selectedPresetName.value, preampGain.value, bands.map(band => band.db))
const close = () => emit('close')

onMounted(() => {
  const { name, preamp } = equalizerStore.getConfig()
  selectedPresetName.value = name
  preampGain.value = preamp
})
</script>
