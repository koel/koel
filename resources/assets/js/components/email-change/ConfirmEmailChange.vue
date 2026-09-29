<template>
  <div class="flex items-center justify-center h-screen flex-col">
    <div v-if="newEmail" class="w-full sm:w-[320px] p-7 sm:bg-k-fg-10 rounded-lg flex flex-col space-y-5">
      <template v-if="changed">
        <p data-testid="changed">Your email address is now {{ newEmail }}.</p>
        <Btn class="text-center" href="/" tag="a">Go to {{ appName }}</Btn>
      </template>

      <form v-else class="flex flex-col space-y-5" @submit.prevent="confirm">
        <p>Change your email address to {{ newEmail }}?</p>
        <Btn :disabled="loading" type="submit">Confirm</Btn>
      </form>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { authService } from '@/services/authService'
import { base64Decode } from '@/utils/crypto'
import { logger } from '@/utils/logger'
import { useBranding } from '@/composables/useBranding'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useRouter } from '@/composables/useRouter'

import Btn from '@/components/ui/form/Btn.vue'

const { getRouteParam } = useRouter()
const { name: appName } = useBranding()
const { handleHttpError } = useErrorHandler('dialog')
const { toastError } = useMessageToaster()

const INVALID_LINK_MESSAGE = 'The link is invalid or has expired.'

const signedPath = ref('')
const newEmail = ref('')
const changed = ref(false)
const loading = ref(false)

try {
  signedPath.value = base64Decode(decodeURIComponent(getRouteParam('payload')!))
  newEmail.value = new URLSearchParams(signedPath.value.split('?')[1]).get('email') ?? ''
} catch (error: unknown) {
  logger.error(error)
}

if (!newEmail.value) {
  toastError(INVALID_LINK_MESSAGE)
}

const confirm = async () => {
  try {
    loading.value = true
    await authService.confirmEmailChange(signedPath.value)
    changed.value = true
  } catch (error: unknown) {
    handleHttpError(error, {
      403: INVALID_LINK_MESSAGE,
      409: 'Another account already uses this email address.',
    })
  } finally {
    loading.value = false
  }
}
</script>
