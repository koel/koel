<template>
  <form
    class="min-w-full sm:min-w-[480px] sm:bg-white/10 p-7 rounded-xl"
    data-testid="forgot-password-form"
    @submit.prevent="handleSubmit"
  >
    <h1 class="text-2xl mb-4">Forgot Password</h1>

    <FormRow>
      <div class="flex flex-col gap-3 sm:flex-row sm:gap-0 sm:content-stretch">
        <TextInput
          v-model="data.email"
          class="flex-1 sm:rounded-l sm:rounded-r-none"
          placeholder="Your email address" required
          type="email"
        />
        <Btn :disabled="loading" class="sm:rounded-l-none sm:rounded-r" type="submit">Reset Password</Btn>
        <Btn :disabled="loading" class="!text-k-text-secondary" transparent @click="cancel">Cancel</Btn>
      </div>
    </FormRow>
  </form>
</template>

<script lang="ts" setup>
import { authService } from '@/services/authService'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const emit = defineEmits<{ (e: 'cancel'): void }>()

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
    toastSuccess('Password reset link sent. Please check your mailbox.')
  },
  onError: error => handleHttpError(error, { 404: 'No user with this email address found.' }),
})

const cancel = () => {
  data.email = ''
  emit('cancel')
}
</script>
