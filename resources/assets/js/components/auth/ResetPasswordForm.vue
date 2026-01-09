<template>
  <div class="flex items-center justify-center h-screen">
    <form
      v-if="validPayload"
      class="flex flex-col gap-3 sm:w-[480px] sm:bg-k-fg-10 sm:rounded-lg p-7"
      @submit.prevent="handleSubmit"
    >
      <h1 class="text-2xl mb-2">{{ t('auth.setNewPassword') }}</h1>
      <div>
        <FormRow>
          <PasswordField v-model="data.password" minlength="10" :placeholder="t('auth.newPassword')" required />
          <template #help>{{ t('auth.passwordRequirements') }}</template>
        </FormRow>
      </div>
      <div>
        <Btn :disabled="loading" type="submit">{{ t('auth.save') }}</Btn>
      </div>
    </form>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
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
const { t } = useI18n()
const { toastSuccess, toastError } = useMessageToaster()

const email = ref('')
const token = ref('')

const validPayload = computed(() => email.value && token.value)

try {
  [email.value, token.value] = base64Decode(decodeURIComponent(getRouteParam('payload')!)).split('|')
} catch (error: unknown) {
  logger.error(error)
  toastError(t('auth.invalidResetLink'))
}

const { data, loading, handleSubmit } = useForm<{ password: string }>({
  initialValues: {
    password: '',
  },
  useOverlay: false,
  onSubmit: async ({ password }) => {
    await authService.resetPassword(email.value, password, token.value)
    toastSuccess(t('auth.passwordSet'))
    await authService.login(email.value, password)
  },
  onSuccess: () => setTimeout(() => go('/', true)),
})
</script>
