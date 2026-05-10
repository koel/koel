<template>
  <header class="flex gap-2 items-center">
    <template v-if="!saveDialogOpen">
      <SelectBox
        :model-value="selectedKey"
        class="!bg-black/30 !text-white"
        title="Select equalizer"
        @update:model-value="key => emit('select', key)"
      >
        <option :value="null" disabled>Preset</option>
        <optgroup label="Built-in">
          <option v-for="preset in builtInPresets" :key="preset.name!" :value="`builtin:${preset.name}`">
            {{ preset.name }}
          </option>
        </optgroup>
        <optgroup v-if="customPresets.length" label="Custom">
          <option v-for="preset in customPresets" :key="preset.id!" :value="`custom:${preset.id}`">
            {{ preset.name }}
          </option>
        </optgroup>
      </SelectBox>

      <Btn v-if="isModified" variant="success" bordered @click.prevent="saveDialogOpen = true">Save as…</Btn>
      <Btn v-if="customSelected" variant="destructive" bordered @click.prevent="emit('delete')">Delete</Btn>
    </template>

    <EqualizerSavePresetForm v-else @submit="commitSave" @cancel="saveDialogOpen = false" />
  </header>
</template>

<script lang="ts" setup>
import { ref } from 'vue'

import Btn from '@/components/ui/form/Btn.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import EqualizerSavePresetForm from '@/components/ui/equalizer/EqualizerSavePresetForm.vue'

defineProps<{
  selectedKey: string | null
  builtInPresets: EqualizerPreset[]
  customPresets: EqualizerPreset[]
  isModified: boolean
  customSelected: boolean
}>()

const emit = defineEmits<{
  (e: 'select', key: string | null): void
  (e: 'save', name: string): void
  (e: 'delete'): void
}>()

const saveDialogOpen = ref(false)

const commitSave = (name: string) => {
  emit('save', name)
  saveDialogOpen.value = false
}
</script>
