<template>
  <div class="space-y-4">
    <p class="text-k-success">Two-factor authentication is active on your account.</p>

    <form v-if="action" class="space-y-3 max-w-md" data-testid="two-factor-manage-form" @submit.prevent="handleSubmit">
      <FormRow>
        <template #label>
          Enter a code from your authenticator app or a recovery code to
          {{ action === 'disable' ? 'disable' : 'regenerate recovery codes' }}.
        </template>
        <TextInput v-model="data.code" v-koel-focus autocomplete="one-time-code" placeholder="123 456" required />
      </FormRow>
      <div class="flex gap-2">
        <Btn type="submit">Submit</Btn>
        <Btn type="button" variant="ghost" @click.prevent="cancel">Cancel</Btn>
      </div>
    </form>

    <div v-else class="flex gap-2">
      <Btn type="button" @click.prevent="action = 'regenerate'">Regenerate Recovery Codes</Btn>
      <Btn type="button" variant="destructive" @click.prevent="action = 'disable'">Disable</Btn>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { authService } from '@/services/authService'
import { useDialogBox } from '@/composables/useDialogBox'
import { useForm } from '@/composables/useForm'
import { useMessageToaster } from '@/composables/useMessageToaster'

import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

type Action = 'regenerate' | 'disable'

const emit = defineEmits<{ (e: 'regenerated', codes: string[]): void; (e: 'disabled'): void }>()

const { toastSuccess, toastError } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const action = ref<Action | null>(null)

const cancel = () => {
  action.value = null
  data.code = ''
}

const { data, handleSubmit } = useForm<{ code: string }>({
  initialValues: { code: '' },
  useOverlay: false,
  onSubmit: async ({ code }) => {
    if (action.value === 'regenerate') {
      const result = await authService.regenerateRecoveryCodes(code)
      return { kind: 'regenerated' as const, codes: result.recovery_codes }
    }

    if (!(await showConfirmDialog('Disable two-factor authentication?'))) {
      return { kind: 'cancelled' as const }
    }

    await authService.disableTwoFactor(code)
    return { kind: 'disabled' as const }
  },
  onSuccess: result => {
    if (result.kind === 'regenerated') {
      toastSuccess('Recovery codes regenerated.')
      emit('regenerated', result.codes)
    } else if (result.kind === 'disabled') {
      toastSuccess('Two-factor authentication disabled.')
      emit('disabled')
    }

    cancel()
  },
  onError: () => toastError('Invalid code.'),
})
</script>
