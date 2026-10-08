<template>
  <button
    v-if="supported"
    class="opacity-70 hover:opacity-100 flex items-center gap-2 px-3 py-2 border border-k-fg-20 rounded-sm"
    type="button"
    @click.prevent="logIn"
  >
    <FingerprintIcon :size="16" />
    <span class="text-sm">Log in with a passkey</span>
  </button>
</template>

<script lang="ts" setup>
import { FingerprintIcon } from 'lucide-vue-next'
import { isPasskeyPromptDismissed, isPasskeySupported, passkeyService } from '@/services/passkeyService'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { logger } from '@/utils/logger'

const emit = defineEmits<{ (e: 'loggedIn'): void }>()

const supported = isPasskeySupported()
const { toastError } = useMessageToaster()

const logIn = async () => {
  try {
    await passkeyService.logIn()
    emit('loggedIn')
  } catch (error: unknown) {
    if (isPasskeyPromptDismissed(error)) {
      return
    }

    logger.error('Passkey login error: ', error)
    toastError('Passkey login failed.')
  }
}
</script>
