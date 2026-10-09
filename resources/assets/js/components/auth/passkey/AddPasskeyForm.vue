<template>
  <form class="md:w-[480px] min-w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Add a Passkey</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>Passkey name</template>
        <TextInput v-model="data.name" v-koel-focus name="name" placeholder="MacBook, YubiKey…" required />
      </FormRow>

      <template v-if="confirmsWithPassword">
        <FormRow>
          <template #label>Your password</template>
          <PasswordField v-model="data.password" name="password" required />
        </FormRow>

        <TwoFactorChallengeInput v-if="currentUser.two_factor" v-model="data.code">
          <template #totp-label>
            <span class="text-k-fg">Code from your authenticator app</span>
          </template>
          <template #recovery-label>
            <span class="text-k-fg">Recovery code</span>
          </template>
        </TwoFactorChallengeInput>

        <button
          v-if="hasPasskeys"
          class="text-[.95rem] text-k-highlight hover:text-k-fg"
          type="button"
          @click.prevent="confirmsWithPasskey = true"
        >
          Use a passkey instead
        </button>
      </template>

      <p v-if="confirmsWithPasskey" class="text-[.95rem] text-k-fg-70" data-testid="confirm-with-passkey-note">
        You'll confirm it's you with one of your passkeys first.
        <button
          v-if="canUsePassword"
          class="text-k-highlight hover:text-k-fg"
          type="button"
          @click.prevent="confirmsWithPasskey = false"
        >
          Use your password instead
        </button>
      </p>
    </main>

    <footer>
      <Btn type="submit">Add</Btn>
      <Btn type="button" variant="ghost" @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import {
  isPasskeyAddressRejected,
  isPasskeyPromptDismissed,
  PASSKEY_ADDRESS_REJECTED_MESSAGE,
  passkeyService,
} from '@/services/passkeyService'
import { userStore } from '@/stores/userStore'
import { eventBus } from '@/utils/eventBus'
import { useDialogBox } from '@/composables/useDialogBox'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useForm } from '@/composables/useForm'
import { useMessageToaster } from '@/composables/useMessageToaster'

import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import TwoFactorChallengeInput from '@/components/auth/two-factor/TwoFactorChallengeInput.vue'

const props = defineProps<{ hasPasskeys: boolean }>()

const emit = defineEmits<{ (e: 'close'): void }>()

const currentUser = userStore.current
const canUsePassword = !currentUser.sso_provider

const confirmsWithPasskey = ref(props.hasPasskeys)
const confirmsWithPassword = computed(() => !confirmsWithPasskey.value && canUsePassword)

const { showConfirmDialog, showErrorDialog } = useDialogBox()
const { handleHttpError } = useErrorHandler('dialog')
const { toastSuccess } = useMessageToaster()

const close = () => emit('close')

const proveIdentity = async ({ password, code }: { password: string; code: string }) => {
  if (confirmsWithPasskey.value) {
    return await passkeyService.confirmIdentity()
  }

  return confirmsWithPassword.value ? { password, code } : {}
}

const { data, isPristine, handleSubmit } = useForm<{ name: string; password: string; code: string }>({
  initialValues: { name: '', password: '', code: '' },
  useOverlay: false,
  validator: ({ name }) => name.trim().length > 0,
  onSubmit: async formData => await passkeyService.add(formData.name.trim(), await proveIdentity(formData)),
  onSuccess: (passkey: Passkey) => {
    eventBus.emit('PASSKEY_ADDED', passkey)
    close()
    toastSuccess('Passkey added.')
  },
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
    close()
  }
}
</script>
