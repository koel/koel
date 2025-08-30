<template>
  <div class="flex items-center justify-center h-screen">
    <form
      v-if="validPayload"
      class="flex flex-col gap-3 sm:w-[480px] sm:bg-white/10 sm:rounded-lg p-7"
      @submit.prevent="handleSubmit"
    >
      <h1 class="text-2xl mb-2">Set New Password</h1>
      <div>
        <FormRow>
          <PasswordField v-model="data.password" minlength="10" placeholder="New password" required />
          <template #help>Min. 10 characters. Should be a mix of characters, numbers, and symbols.</template>
        </FormRow>
      </div>
      <div>
        <Btn :disabled="loading" type="submit">Save</Btn>
      </div>
    </form>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { authService } from '@/services/authService'
import { base64Decode } from '@/utils/crypto'
import { logger } from '@/utils/logger'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useRouter } from '@/composables/useRouter'
import { useForm } from '@/composables/useForm'

import PasswordField from '@/components/ui/form/PasswordField.vue'
import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { getRouteParam, go } = useRouter()
const { toastSuccess, toastError } = useMessageToaster()

const email = ref('')
const token = ref('')

const validPayload = computed(() => email.value && token.value)

try {
  [email.value, token.value] = base64Decode(decodeURIComponent(getRouteParam('payload')!)).split('|')
} catch (error: unknown) {
  logger.error(error)
  toastError('Invalid reset password link.')
}

const { data, loading, handleSubmit } = useForm<{ password: string }>({
  initialValues: {
    password: '',
  },
  useOverlay: false,
  onSubmit: async ({ password }) => {
    await authService.resetPassword(email.value, password, token.value)
    toastSuccess('Password set.')
    await authService.login(email.value, password)
  },
  onSuccess: () => setTimeout(() => go('/', true)),
})
</script>
