<template>
  <div class="flex items-center justify-center min-h-screen my-0 mx-auto flex-col gap-5">
    <form
      v-show="!showingForgotPasswordForm"
      class="w-full sm:w-[288px] sm:border duration-500 p-7 rounded-xl border-transparent sm:bg-white/10 space-y-3"
      :class="{ error: failed }"
      data-testid="login-form"
      @submit.prevent="login"
    >
      <div class="text-center mb-8">
        <img class="inline-block" alt="Koel's logo" src="@/../img/logo.svg" width="156">
      </div>

      <FormRow>
        <TextInput v-model="email" autofocus placeholder="Email Address" required type="email" />
      </FormRow>

      <FormRow>
        <PasswordField v-model="password" placeholder="Password" required />
      </FormRow>

      <FormRow>
        <Btn type="submit">Log In</Btn>
      </FormRow>

      <FormRow v-if="canResetPassword">
        <a class="text-right text-[.95rem] text-k-text-secondary" role="button" @click.prevent="showForgotPasswordForm">
          Forgot password?
        </a>
      </FormRow>
    </form>

    <div v-if="ssoProviders.length" v-show="!showingForgotPasswordForm" class="flex gap-3 items-center">
      <GoogleLoginButton v-if="ssoProviders.includes('Google')" @error="onSSOError" @success="onSSOSuccess" />
    </div>

    <ForgotPasswordForm v-if="showingForgotPasswordForm" @cancel="showingForgotPasswordForm = false" />
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { authService } from '@/services'
import { logger } from '@/utils'
import { useMessageToaster } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'
import ForgotPasswordForm from '@/components/auth/ForgotPasswordForm.vue'
import GoogleLoginButton from '@/components/auth/sso/GoogleLoginButton.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const DEMO_ACCOUNT = {
  email: 'demo@koel.dev',
  password: 'demo'
}

const canResetPassword = window.MAILER_CONFIGURED && !window.IS_DEMO
const ssoProviders = window.SSO_PROVIDERS || []

const email = ref(window.IS_DEMO ? DEMO_ACCOUNT.email : '')
const password = ref(window.IS_DEMO ? DEMO_ACCOUNT.password : '')
const failed = ref(false)
const showingForgotPasswordForm = ref(false)

const showForgotPasswordForm = () => (showingForgotPasswordForm.value = true)

const emit = defineEmits<{ (e: 'loggedin'): void }>()

const login = async () => {
  try {
    await authService.login(email.value, password.value)
    failed.value = false

    // Reset the password so that the next login will have this field empty.
    password.value = ''

    emit('loggedin')
  } catch (err) {
    failed.value = true
    window.setTimeout(() => (failed.value = false), 2000)
  }
}

const onSSOError = (error: any) => {
  logger.error('SSO error: ', error)
  useMessageToaster().toastError('Login failed. Please try again.')
}

const onSSOSuccess = (token: CompositeToken) => {
  authService.setTokensUsingCompositeToken(token)
  emit('loggedin')
}
</script>

<style lang="postcss" scoped>
/**
 * I like to move it move it
 * I like to move it move it
 * I like to move it move it
 * You like to - move it!
 */
@keyframes shake {
  8%, 41% {
    transform: translateX(-10px);
  }
  25%, 58% {
    transform: translateX(10px);
  }
  75% {
    transform: translateX(-5px);
  }
  92% {
    transform: translateX(5px);
  }
  0%, 100% {
    transform: translateX(0);
  }
}

form {
  &.error {
    @apply border-red-500;
    animation: shake .5s;
  }
}
</style>
