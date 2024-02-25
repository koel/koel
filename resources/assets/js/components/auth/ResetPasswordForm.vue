<template>
  <div class="reset-password-wrapper">
    <form v-if="validPayload" @submit.prevent="submit">
      <h1 class="font-size-1.5">Set New Password</h1>
      <div>
        <label>
          <PasswordField v-model="password" placeholder="New password" required />
          <span class="help">Min. 10 characters. Should be a mix of characters, numbers, and symbols.</span>
        </label>
      </div>
      <div>
        <Btn :disabled="loading" type="submit">Save</Btn>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { authService } from '@/services'
import { base64Decode } from '@/utils'
import { useMessageToaster, useRouter } from '@/composables'

import PasswordField from '@/components/ui/PasswordField.vue'
import Btn from '@/components/ui/Btn.vue'

const { getRouteParam, go } = useRouter()
const { toastSuccess, toastError } = useMessageToaster()

const email = ref('')
const token = ref('')
const password = ref('')
const loading = ref(false)

const validPayload = computed(() => email.value && token.value)

try {
  [email.value, token.value] = base64Decode(decodeURIComponent(getRouteParam('payload')!)).split('|')
} catch (err) {
  toastError('Invalid reset password link.')
}

const submit = async () => {
  try {
    loading.value = true
    await authService.resetPassword(email.value, password.value, token.value)
    toastSuccess('Password updated. Please log in with your new password.')
    setTimeout(() => go('/', true), 3000)
  } catch (err: any) {
    toastError(err.response?.data?.message || 'Failed to set new password. Please try again.')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
.reset-password-wrapper {
  @include vertical-center;

  height: 100vh;
}

h1 {
  margin-bottom: .75rem;
}

form {
  width: 480px;
  background: rgba(255, 255, 255, .08);
  border-radius: .6rem;
  padding: 1.8rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;

  .help {
    display: block;
    margin-top: .8rem;
  }
}
</style>
