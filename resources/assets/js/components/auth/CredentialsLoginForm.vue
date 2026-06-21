<template>
  <AuthFormCard :failed data-testid="login-form" @submit="handleSubmit">
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
      <a class="text-right text-[.95rem] text-k-fg-70" role="button" @click.prevent="$emit('forgotPassword')">
        Forgot password?
      </a>
    </FormRow>
  </AuthFormCard>
</template>

<script lang="ts" setup>
import { onBeforeUnmount, ref } from 'vue'
import { authService } from '@/services/authService'
import { logger } from '@/utils/logger'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import AuthFormCard from '@/components/auth/AuthFormCard.vue'

const emit = defineEmits<{
  (e: 'loggedIn'): void
  (e: 'twoFactorRequired', loginToken: string): void
  (e: 'forgotPassword'): void
}>()

const demoAccount = window.KOEL.demo_account || {
  email: 'demo@koel.dev',
  password: 'demo',
}

const failed = ref(false)
const canResetPassword = window.KOEL.mailer_configured && !window.KOEL.is_demo
const emailPlaceholder = window.KOEL.is_demo ? demoAccount.email : 'Your email address'
const passwordPlaceholder = window.KOEL.is_demo ? demoAccount.password : 'Your password'

let errorResetTimer: number | null = null

const clearErrorResetTimer = () => {
  if (errorResetTimer !== null) {
    window.clearTimeout(errorResetTimer)
    errorResetTimer = null
  }
}

const { data, handleSubmit } = useForm<{ email: string; password: string }>({
  initialValues: window.KOEL.is_demo
    ? demoAccount
    : {
        email: '',
        password: '',
      },
  onSubmit: async ({ email, password }) => await authService.login(email, password),
  onSuccess: challenge => {
    failed.value = false
    data.password = ''

    if (challenge) {
      emit('twoFactorRequired', challenge.login_token)
      return
    }

    emit('loggedIn')
  },
  onError: (error: unknown) => {
    failed.value = true
    logger.error(error)
    clearErrorResetTimer()
    errorResetTimer = window.setTimeout(() => {
      failed.value = false
      errorResetTimer = null
    }, 2000)
  },
})

onBeforeUnmount(clearErrorResetTimer)
</script>
