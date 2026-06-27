<template>
  <div class="flex items-center justify-center min-h-screen my-0 mx-auto flex-col gap-5">
    <TwoFactorChallengeForm
      v-if="twoFactorLoginToken"
      :login-token="twoFactorLoginToken"
      @cancel="twoFactorLoginToken = ''"
      @verified="$emit('loggedIn')"
    />

    <ForgotPasswordForm v-else-if="showingForgotPasswordForm" @cancel="showingForgotPasswordForm = false" />

    <template v-else>
      <CredentialsLoginForm
        @forgot-password="showingForgotPasswordForm = true"
        @logged-in="$emit('loggedIn')"
        @two-factor-required="twoFactorLoginToken = $event"
      />
      <SsoLoginOptions @logged-in="$emit('loggedIn')" />
    </template>
  </div>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { authService } from '@/services/authService'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { defineAsyncComponent } from '@/utils/helpers'

const CredentialsLoginForm = defineAsyncComponent(() => import('@/components/auth/CredentialsLoginForm.vue'))
const ForgotPasswordForm = defineAsyncComponent(() => import('@/components/auth/ForgotPasswordForm.vue'))
const SsoLoginOptions = defineAsyncComponent(() => import('@/components/auth/sso/SsoLoginOptions.vue'))
const TwoFactorChallengeForm = defineAsyncComponent(
  () => import('@/components/auth/two-factor/TwoFactorChallengeForm.vue'),
)

defineEmits<{ (e: 'loggedIn'): void }>()

const { toastWarning } = useMessageToaster()

const showingForgotPasswordForm = ref(false)
const twoFactorLoginToken = ref('')

onMounted(() => {
  if (authService.hasRedirect()) {
    toastWarning('Please log in first.')
  }
})
</script>
