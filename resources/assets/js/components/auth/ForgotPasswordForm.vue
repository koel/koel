<template>
  <AuthFormCard data-testid="forgot-password-form" @submit="handleSubmit">
    <p class="text-[.95rem] text-k-fg-70 mb-4">Enter your email address and we'll send you a password reset link.</p>

    <FormRow>
      <TextInput v-model="data.email" placeholder="Your email address" required type="email" />
    </FormRow>

    <Btn class="w-full" :disabled="loading" type="submit">Reset Password</Btn>
    <Btn class="w-full" bordered :disabled="loading" type="button" @click="cancel">Cancel</Btn>
  </AuthFormCard>
</template>

<script lang="ts" setup>
import { authService } from '@/services/authService'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import AuthFormCard from '@/components/auth/AuthFormCard.vue'

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
