<template>
  <header class="flex gap-2 items-center">
    <template v-if="!saveDialogOpen">
      <SelectBox v-model="selectedKey" class="!bg-black/30 !text-white" title="Select equalizer">
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

      <Btn v-if="isModified" variant="ghost" @click.prevent="saveDialogOpen = true">Save as…</Btn>
      <Btn v-if="customSelected" variant="ghost" @click.prevent="emit('delete')">Delete</Btn>
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
  builtInPresets: EqualizerPreset[]
  customPresets: EqualizerPreset[]
  isModified: boolean
  customSelected: boolean
}>()

const selectedKey = defineModel<string | null>('selectedKey', { required: true })

const emit = defineEmits<{
  (e: 'save', name: string): void
  (e: 'delete'): void
}>()

const saveDialogOpen = ref(false)

const commitSave = (name: string) => {
  emit('save', name)
  saveDialogOpen.value = false
}
</script>
