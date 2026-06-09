<template>
  <div class="space-y-4">
    <AlertBox>Two-factor authentication is enabled.</AlertBox>

    <form v-if="action" class="space-y-3" data-testid="two-factor-manage-form" @submit.prevent="handleSubmit">
      <TwoFactorChallengeInput v-model="data.code" @complete="handleSubmit">
        <template #totp-label>
          <p class="text-[.95rem] text-k-fg-70">
            Enter a code from your authenticator app to
            {{ action === 'disable' ? 'disable two-factor authentication' : 'regenerate recovery codes' }}.
          </p>
        </template>
        <template #recovery-label>
          <p class="text-[.95rem] text-k-fg-70">
            Enter a recovery code to
            {{ action === 'disable' ? 'disable two-factor authentication' : 'regenerate recovery codes' }}.
          </p>
        </template>
      </TwoFactorChallengeInput>

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
import TwoFactorChallengeInput from '@/components/auth/two-factor/TwoFactorChallengeInput.vue'
import AlertBox from '@/components/ui/AlertBox.vue'

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
