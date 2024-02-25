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

    <ForgotPasswordForm v-if="showingForgotPasswordForm" @cancel="showingForgotPasswordForm = false" />
  </div>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { isDemo } from '@/utils'
import { authService } from '@/services'

import Btn from '@/components/ui/Btn.vue'
import PasswordField from '@/components/ui/PasswordField.vue'
import ForgotPasswordForm from '@/components/auth/ForgotPasswordForm.vue'

const DEMO_ACCOUNT = {
  email: 'demo@koel.dev',
  password: 'demo'
}

const canResetPassword = window.MAILER_CONFIGURED && !isDemo()

const email = ref(isDemo() ? DEMO_ACCOUNT.email : '')
const password = ref(isDemo() ? DEMO_ACCOUNT.password : '')
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
  @include vertical-center();

  height: 100vh;
}

form {
  width: 280px;
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

  @media only screen and (max-width: 414px) {
    border: 0;
    background: transparent;
  }
}
</style>
