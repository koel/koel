<template>
  <header class="flex gap-2 items-center">
    <template v-if="!saveDialogOpen">
      <SelectBox
        :model-value="selectedId"
        class="!bg-black/30 !text-white"
        title="Select equalizer"
        @update:model-value="id => emit('select', id)"
      >
        <option :value="null" disabled>Preset</option>
        <template v-if="customPresets.length">
          <optgroup label="Built-in">
            <option v-for="preset in builtInPresets" :key="preset.id ?? ''" :value="preset.id ?? ''">
              {{ preset.name }}
            </option>
          </optgroup>
          <optgroup label="Custom">
            <option v-for="preset in customPresets" :key="preset.id ?? ''" :value="preset.id ?? ''">
              {{ preset.name }}
            </option>
          </optgroup>
        </template>
        <option v-for="preset in builtInPresets" v-else :key="preset.id ?? ''" :value="preset.id ?? ''">
          {{ preset.name }}
        </option>
      </SelectBox>

      <Btn v-if="isModified" variant="ghost" @click.prevent="saveDialogOpen = true">Save as…</Btn>
      <Btn v-if="customSelected" variant="ghost" @click.prevent="emit('delete')">Delete</Btn>
    </template>

    <EqualizerSavePresetForm v-else @submit="commitSave" @cancel="saveDialogOpen = false" />
  </header>
</template>

<script lang="ts" setup>
import { ref, toRef } from 'vue'
import { equalizerPresets as builtInPresets } from '@/config/audio'
import { equalizerStore } from '@/stores/equalizerStore'

import Btn from '@/components/ui/form/Btn.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import EqualizerSavePresetForm from '@/components/ui/equalizer/EqualizerSavePresetForm.vue'

defineProps<{
  selectedId: string | null
  isModified: boolean
  customSelected: boolean
}>()

const emit = defineEmits<{
  (e: 'select', id: string | null): void
  (e: 'save', name: string): void
  (e: 'delete'): void
}>()

const customPresets = toRef(equalizerStore.state, 'customPresets')
const saveDialogOpen = ref(false)

const commitSave = (name: string) => {
  emit('save', name)
  saveDialogOpen.value = false
}
</script>
