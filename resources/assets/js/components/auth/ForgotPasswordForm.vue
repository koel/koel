<template>
  <form
    class="min-w-full sm:min-w-[480px] sm:bg-white/10 p-7 rounded-xl"
    data-testid="forgot-password-form"
    @submit.prevent="requestResetPasswordLink"
  >
    <h1 class="text-2xl mb-4">Forgot Password</h1>

    <FormRow>
      <div class="flex flex-col gap-3 sm:flex-row sm:gap-0 sm:content-stretch">
        <TextInput
          v-model="email"
          placeholder="Your email address"
          required type="email"
          class="flex-1 sm:rounded-l sm:rounded-r-none"
        />
        <Btn :disabled="loading" type="submit" class="sm:rounded-l-none sm:rounded-r">Reset Password</Btn>
        <Btn :disabled="loading" class="!text-k-text-secondary" transparent @click="cancel">Cancel</Btn>
      </div>
    </FormRow>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { authService } from '@/services'
import { useErrorHandler, useMessageToaster } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const emit = defineEmits<{ (e: 'cancel'): void }>()
const email = ref('')
const loading = ref(false)

const cancel = () => {
  email.value = ''
  emit('cancel')
}

const requestResetPasswordLink = async () => {
  try {
    loading.value = true
    await authService.requestResetPasswordLink(email.value)
    useMessageToaster().toastSuccess('Password reset link sent. Please check your email.')
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error, { 404: 'No user with this email address found.' })
  } finally {
    loading.value = false
  }
}
</script>
