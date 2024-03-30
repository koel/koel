<template>
  <div class="login-wrapper">
    <form
      v-show="!showingForgotPasswordForm"
      :class="{ error: failed }"
      data-testid="login-form"
      @submit.prevent="login"
    >
      <div class="logo">
        <img alt="Koel's logo" src="@/../img/logo.svg" width="156">
      </div>

      <input v-model="email" autofocus placeholder="Email Address" required type="email">
      <PasswordField v-model="password" placeholder="Password" required />

      <Btn type="submit">Log In</Btn>
      <a
        v-if="canResetPassword"
        class="reset-password"
        role="button"
        @click.prevent="showForgotPasswordForm"
      >
        Forgot password?
      </a>
    </form>

    <div v-if="ssoProviders.length" v-show="!showingForgotPasswordForm" class="sso">
      <GoogleLoginButton v-if="ssoProviders.includes('Google')" @error="onSSOError" @success="onSSOSuccess" />
    </div>

    <ForgotPasswordForm v-if="showingForgotPasswordForm" @cancel="showingForgotPasswordForm = false" />
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { authService, CompositeToken } from '@/services'
import { logger } from '@/utils'
import { useMessageToaster, useRouter } from '@/composables'

import Btn from '@/components/ui/Btn.vue'
import PasswordField from '@/components/ui/PasswordField.vue'
import ForgotPasswordForm from '@/components/auth/ForgotPasswordForm.vue'
import GoogleLoginButton from '@/components/auth/sso/GoogleLoginButton.vue'

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

<style lang="scss" scoped>
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

.login-wrapper {
  min-height: 100vh;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  justify-content: center;
  align-items: center;
}

.sso {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

form {
  width: 276px;
  padding: 1.8rem;
  background: rgba(255, 255, 255, .08);
  border-radius: .6rem;
  border: 1px solid transparent;
  transition: .5s;
  display: flex;
  flex-direction: column;
  gap: 1rem;

  &.error {
    border-color: var(--color-red);
    animation: shake .5s;
  }

  .logo {
    text-align: center;
  }

  .reset-password {
    display: block;
    text-align: right;
    font-size: .95rem;
  }

  @media only screen and (max-width: 480px) {
    width: 100vw;
    border: 0;
    background: transparent;
  }
}
</style>
