<template>
  <div class="select-none w-full flex flex-col" tabindex="0" @keydown.esc="close">
    <EqualizerHeader
      :selected-key="selectedKey"
      :built-in-presets="builtInPresets"
      :custom-presets="customPresets"
      :is-modified="isModified"
      :custom-selected="customSelected"
      @select="applySelection"
      @save="commitSave"
      @delete="confirmDelete"
    />

    <main>
      <EqualizerBands ref="bandsRef" :bands="bands" @user-change="onUserChange" @commit="save" />
    </main>

    <footer class="border-t-k-fg-5">
      <Btn @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, toRef } from 'vue'
import { equalizerStore } from '@/stores/equalizerStore'
import { audioService } from '@/services/audioService'
import { equalizerPresets as builtInPresets } from '@/config/audio'
import { useDialogBox } from '@/composables/useDialogBox'
import {
  builtInKey,
  builtInNameFromKey,
  customIdFromKey,
  customKey,
  isBuiltInKey,
  isCustomKey,
} from '@/utils/equalizerKey'

import Btn from '@/components/ui/form/Btn.vue'
import EqualizerBands from '@/components/ui/equalizer/EqualizerBands.vue'
import EqualizerHeader from '@/components/ui/equalizer/EqualizerHeader.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { showConfirmDialog } = useDialogBox()

const bands = audioService.bands
const selectedKey = ref<string | null>(null)
const customPresets = toRef(equalizerStore.state, 'customPresets')
const bandsRef = ref<InstanceType<typeof EqualizerBands>>()

const isModified = computed(() => selectedKey.value === null)
const customSelected = computed(() => isCustomKey(selectedKey.value))

const resolvePreset = (key: string | null): EqualizerPreset | null => {
  if (isBuiltInKey(key)) {
    return equalizerStore.getBuiltInPresetByName(builtInNameFromKey(key)) ?? null
  }

  if (isCustomKey(key)) {
    return equalizerStore.getCustomPresetById(customIdFromKey(key)) ?? null
  }

  return null
}

const keyForPreset = (preset: EqualizerPreset): string | null => {
  if (preset.id) {
    return customKey(preset.id)
  }

  if (preset.name !== null) {
    return builtInKey(preset.name)
  }

  return null
}

const save = () =>
  equalizerStore.saveConfig(
    resolvePreset(selectedKey.value),
    bandsRef.value?.getPreamp() ?? 0,
    bands.map(band => band.db),
  )

const applySelection = async (key: string | null) => {
  selectedKey.value = key

  if (key !== null) {
    await bandsRef.value?.loadPreset(resolvePreset(key) ?? builtInPresets[0], bands)
  }

  save()
}

const onUserChange = () => {
  selectedKey.value = null
}

const commitSave = async (name: string) => {
  const created = await equalizerStore.saveCustomPreset(
    name,
    bandsRef.value?.getPreamp() ?? 0,
    bands.map(band => band.db),
  )

  selectedKey.value = customKey(created.id!)
  save()
}

const confirmDelete = async () => {
  if (!isCustomKey(selectedKey.value)) {
    return
  }

  if (!(await showConfirmDialog('Delete this preset?'))) {
    return
  }

  await equalizerStore.deleteCustomPreset(customIdFromKey(selectedKey.value))
  selectedKey.value = null
  save()
}

const close = () => emit('close')

onMounted(async () => {
  equalizerStore.init()
  const preset = equalizerStore.getConfig()
  await bandsRef.value?.loadPreset(preset, bands)
  selectedKey.value = keyForPreset(preset)
})
</script>
