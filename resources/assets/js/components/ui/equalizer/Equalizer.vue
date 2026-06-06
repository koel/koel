<template>
  <div class="select-none w-full flex flex-col" tabindex="0" @keydown.esc="close">
    <EqualizerHeader
      :selected-id="selectedId"
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
import { computed, onMounted, ref } from 'vue'
import { equalizerStore } from '@/stores/equalizerStore'
import { audioService } from '@/services/audioService'
import { equalizerPresets as builtInPresets } from '@/config/audio'
import { useDialogBox } from '@/composables/useDialogBox'

import Btn from '@/components/ui/form/Btn.vue'
import EqualizerBands from '@/components/ui/equalizer/EqualizerBands.vue'
import EqualizerHeader from '@/components/ui/equalizer/EqualizerHeader.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { showConfirmDialog } = useDialogBox()

const bands = audioService.bands
const selectedId = ref<string | null>(null)
const bandsRef = ref<InstanceType<typeof EqualizerBands>>()

const isModified = computed(() => selectedId.value === null)

const customSelected = computed(
  () => selectedId.value !== null && !builtInPresets.some(preset => preset.id === selectedId.value),
)

const save = () =>
  equalizerStore.saveConfig(
    selectedId.value === null ? null : (equalizerStore.getPresetById(selectedId.value) ?? null),
    bandsRef.value?.getPreamp() ?? 0,
    bands.map(band => band.db),
  )

const applySelection = async (id: string | null) => {
  selectedId.value = id

  if (id !== null) {
    await bandsRef.value?.loadPreset(equalizerStore.getPresetById(id) ?? builtInPresets[0], bands)
  }

  save()
}

const onUserChange = () => {
  selectedId.value = null
}

const commitSave = async (name: string) => {
  const created = await equalizerStore.saveCustomPreset(
    name,
    bandsRef.value?.getPreamp() ?? 0,
    bands.map(band => band.db),
  )

  selectedId.value = created.id ?? null
  save()
}

const confirmDelete = async () => {
  if (!customSelected.value || selectedId.value === null) {
    return
  }

  if (!(await showConfirmDialog('Delete this preset?'))) {
    return
  }

  await equalizerStore.deleteCustomPreset(selectedId.value)
  selectedId.value = null
  save()
}

const close = () => emit('close')

onMounted(async () => {
  equalizerStore.init()
  const preset = equalizerStore.getConfig()
  await bandsRef.value?.loadPreset(preset, bands)
  selectedId.value = preset.id ?? null
})
</script>
