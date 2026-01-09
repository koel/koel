<template>
  <form
    class="min-w-full sm:min-w-[480px] sm:bg-k-fg-10 p-7 rounded-xl"
    data-testid="forgot-password-form"
    @submit.prevent="handleSubmit"
  >
    <h1 class="text-2xl mb-4">{{ t('auth.forgotPassword') }}</h1>

    <FormRow>
      <div class="flex flex-col gap-3 sm:flex-row sm:gap-0 sm:content-stretch">
        <TextInput
          v-model="data.email"
          class="flex-1 sm:rounded-l sm:rounded-r-none"
          :placeholder="t('auth.yourEmailAddress')" required
          type="email"
        />
        <Btn :disabled="loading" class="sm:rounded-l-none sm:rounded-r" type="submit">{{ t('auth.resetPassword') }}</Btn>
        <Btn :disabled="loading" transparent @click="cancel">{{ t('auth.cancel') }}</Btn>
      </div>
    </FormRow>
  </form>
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import { authService } from '@/services/authService'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const emit = defineEmits<{ (e: 'cancel'): void }>()

const { t } = useI18n()
const { handleHttpError } = useErrorHandler()
const { toastSuccess } = useMessageToaster()

const { data, loading, handleSubmit } = useForm<{ email: string }>({
  initialValues: {
    email: '',
  },
  useOverlay: false,
  onSubmit: async ({ email }) => await authService.requestResetPasswordLink(email),
  onSuccess: () => {
    data.email = ''
    toastSuccess(t('auth.passwordResetLinkSent'))
  },
  onError: error => handleHttpError(error, { 404: t('auth.noUserWithEmail') }),
})

const cancel = () => {
  data.email = ''
  emit('cancel')
}
</script>
