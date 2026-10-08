<template>
  <form class="flex flex-wrap items-end gap-3" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <FormRow class="flex-1 min-w-48 max-w-md">
      <template #label>Passkey name</template>
      <TextInput v-model="data.name" v-koel-focus name="name" placeholder="MacBook, YubiKey…" required />
    </FormRow>

    <div class="flex gap-2">
      <Btn type="submit">Add</Btn>
      <Btn type="button" variant="ghost" @click.prevent="maybeClose">Cancel</Btn>
    </div>
  </form>
</template>

<script lang="ts" setup>
import {
  isPasskeyAddressRejected,
  isPasskeyPromptDismissed,
  PASSKEY_ADDRESS_REJECTED_MESSAGE,
  passkeyService,
} from '@/services/passkeyService'
import { useDialogBox } from '@/composables/useDialogBox'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

const emit = defineEmits<{
  (e: 'added', passkey: Passkey): void
  (e: 'cancel'): void
}>()

const { showConfirmDialog, showErrorDialog } = useDialogBox()
const { handleHttpError } = useErrorHandler('dialog')

const { data, isPristine, handleSubmit } = useForm<{ name: string }>({
  initialValues: { name: '' },
  useOverlay: false,
  validator: ({ name }) => name.trim().length > 0,
  onSubmit: async ({ name }) => await passkeyService.add(name.trim()),
  onSuccess: (passkey: Passkey) => emit('added', passkey),
  onError: (error: unknown) => {
    if (isPasskeyPromptDismissed(error)) {
      return
    }

    if (isPasskeyAddressRejected(error)) {
      showErrorDialog(PASSKEY_ADDRESS_REJECTED_MESSAGE)
      return
    }

    handleHttpError(error)
  },
})

const maybeClose = async () => {
  if (isPristine() || (await showConfirmDialog('Discard this passkey?'))) {
    emit('cancel')
  }
}
</script>
