<template>
  <form class="flex gap-2 items-center w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeCancel">
    <TextInput
      v-model="data.name"
      v-koel-focus
      name="preset-name"
      placeholder="Preset name"
      title="Preset name"
      required
    />
    <Btn type="submit" variant="ghost">Save</Btn>
    <Btn type="button" variant="ghost" @click.prevent="maybeCancel">Cancel</Btn>
  </form>
</template>

<script lang="ts" setup>
import { useDialogBox } from '@/composables/useDialogBox'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

const emit = defineEmits<{
  (e: 'submit', name: string): void
  (e: 'cancel'): void
}>()

const { showConfirmDialog } = useDialogBox()

const { data, isPristine, handleSubmit } = useForm<{ name: string }>({
  initialValues: { name: '' },
  validator: ({ name }) => name.trim().length > 0,
  onSubmit: async ({ name }) => emit('submit', name.trim()),
  useOverlay: false,
})

const maybeCancel = async () => {
  if (isPristine() || (await showConfirmDialog('Discard preset name?'))) {
    emit('cancel')
  }
}
</script>
