<template>
  <button
    v-if="supported"
    aria-label="Log in with a passkey"
    class="opacity-70 hover:opacity-100 flex items-center p-2 border border-k-fg-20 rounded-sm"
    title="Log in with a passkey"
    type="button"
    @click.prevent="logIn"
  >
    <FingerprintIcon :size="16" />
  </button>
</template>

<script lang="ts" setup>
import { FingerprintIcon } from 'lucide-vue-next'
import {
  isPasskeyAddressRejected,
  isPasskeyPromptDismissed,
  isPasskeySupported,
  PASSKEY_ADDRESS_REJECTED_MESSAGE,
  passkeyService,
} from '@/services/passkeyService'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'

const emit = defineEmits<{ (e: 'loggedIn'): void }>()

const supported = isPasskeySupported()
const { toastError } = useMessageToaster()
const { handleHttpError } = useErrorHandler('toast')

const logIn = async () => {
  try {
    await passkeyService.logIn()
    emit('loggedIn')
  } catch (error: unknown) {
    if (isPasskeyPromptDismissed(error)) {
      return
    }

    if (isPasskeyAddressRejected(error)) {
      toastError(PASSKEY_ADDRESS_REJECTED_MESSAGE)
      return
    }

    handleHttpError(error)
  }
}
</script>
