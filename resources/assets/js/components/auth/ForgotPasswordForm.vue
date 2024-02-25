<template>
  <form @submit.prevent="requestResetPasswordLink" data-testid="forgot-password-form">
    <h1 class="font-size-1.5">Forgot Password</h1>

    <div>
      <input v-model="email" placeholder="Your email address" required type="email" />
      <Btn :disabled="loading" type="submit">Reset Password</Btn>
      <Btn :disabled="loading" class="text-secondary" transparent @click="cancel">Cancel</Btn>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { authService } from '@/services'
import { useMessageToaster } from '@/composables'

import Btn from '@/components/ui/Btn.vue'

const { toastSuccess, toastError } = useMessageToaster()

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
    toastSuccess('Password reset link sent. Please check your email.')
  } catch (err: any) {
    if (err.response.status === 404) {
      toastError('No user with this email address found.')
    } else {
      toastError('An unknown error occurred.')
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
form {
  min-width: 480px;

  h1 {
    margin-bottom: .75rem;
  }

  > div {
    display: flex;

    input {
      flex: 1;
      border-radius: var(--border-radius-input) 0 0 var(--border-radius-input);
    }

    [type=submit] {
      border-radius: 0 var(--border-radius-input) var(--border-radius-input) 0;
    }
  }
}
</style>
