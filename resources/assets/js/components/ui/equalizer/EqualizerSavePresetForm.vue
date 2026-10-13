<template>
  <form class="flex gap-2 items-center w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <TextInput
      v-model="data.name"
      v-koel-focus
      name="preset-name"
      placeholder="Preset name"
      aria-label="Preset name"
      required
    />
    <Btn type="submit" variant="ghost">Save</Btn>
    <Btn type="button" variant="ghost" @click.prevent="maybeClose">Cancel</Btn>
  </form>
</template>

<script lang="ts" setup>
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

const emit = defineEmits<{
  (e: 'submit', name: string): void
  (e: 'cancel'): void
}>()

const { data, handleSubmit, maybeClose } = useForm<{ name: string }>({
  initialValues: { name: '' },
  validator: ({ name }) => name.trim().length > 0,
  onSubmit: async ({ name }) => emit('submit', name.trim()),
  useOverlay: false,
  onClose: () => emit('cancel'),
  discardConfirmation: 'Discard preset name?',
})
</script>
