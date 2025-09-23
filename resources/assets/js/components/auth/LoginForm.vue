<template>
  <div class="flex items-center justify-center min-h-screen my-0 mx-auto flex-col gap-5">
    <form
      v-show="!showingForgotPasswordForm"
      :class="{ error: failed }"
      class="w-full sm:w-[288px] sm:border duration-500 p-7 rounded-xl border-transparent sm:bg-white/10 space-y-3"
      data-testid="login-form"
      @submit.prevent="handleSubmit"
    >
      <div class="text-center mb-8">
        <img alt="Koel's logo" class="inline-block" src="@/../img/logo.svg" width="156">
      </div>

      <FormRow>
        <TextInput v-model="data.email" autofocus :placeholder="emailPlaceholder" required type="email" />
      </FormRow>

      <FormRow>
        <PasswordField v-model="data.password" :placeholder="passwordPlaceholder" required />
      </FormRow>

      <FormRow>
        <Btn class="w-full" data-testid="submit" type="submit">Log In</Btn>
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
import { onMounted, ref } from 'vue'
import { authService } from '@/services/authService'
import { logger } from '@/utils/logger'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'
import ForgotPasswordForm from '@/components/auth/ForgotPasswordForm.vue'
import GoogleLoginButton from '@/components/auth/sso/GoogleLoginButton.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const emit = defineEmits<{ (e: 'loggedin'): void }>()

const { toastWarning, toastError } = useMessageToaster()

const demoAccount = window.DEMO_ACCOUNT || {
  email: 'demo@koel.dev',
  password: 'demo',
}

const failed = ref(false)
const showingForgotPasswordForm = ref(false)
const canResetPassword = window.MAILER_CONFIGURED && !window.IS_DEMO
const ssoProviders = window.SSO_PROVIDERS || []
const emailPlaceholder = window.IS_DEMO ? demoAccount.email : 'Your email address'
const passwordPlaceholder = window.IS_DEMO ? demoAccount.password : 'Your password'

const { data, handleSubmit } = useForm<{ email: string, password: string }>({
  initialValues: window.IS_DEMO
    ? demoAccount
    : {
        email: '',
        password: '',
      },
  onSubmit: async ({ email, password }) => await authService.login(email, password),
  onSuccess: () => {
    failed.value = false
    // Reset the password so that the next login will have this field empty.
    data.password = ''
    emit('loggedin')
  },
  onError: (error: unknown) => {
    failed.value = true
    logger.error(error)
    window.setTimeout(() => (failed.value = false), 2000)
  },
})

const showForgotPasswordForm = () => (showingForgotPasswordForm.value = true)

const onSSOError = (error: any) => {
  logger.error('SSO error: ', error)
  toastError('Login failed. Please try again.')
}

const onSSOSuccess = (token: CompositeToken) => {
  authService.setTokensUsingCompositeToken(token)
  emit('loggedin')
}

onMounted(() => {
  if (authService.hasRedirect()) {
    toastWarning('Please log in first.')
  }
})
</script>

<style lang="postcss" scoped>
/**
 * I like to move it move it
 * I like to move it move it
 * I like to move it move it
 * You like to - move it!
 */
@keyframes shake {
  8%,
  41% {
    transform: translateX(-10px);
  }
  25%,
  58% {
    transform: translateX(10px);
  }
  75% {
    transform: translateX(-5px);
  }
  92% {
    transform: translateX(5px);
  }
  0%,
  100% {
    transform: translateX(0);
  }
}

form {
  &.error {
    @apply border-red-500;
    animation: shake 0.5s;
  }
}
</style>
