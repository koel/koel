<template>
  <div v-if="ssoProviders.length" class="flex gap-3 items-center">
    <GoogleLoginButton v-if="ssoProviders.includes('Google')" @error="onError" @success="onSuccess" />
    <OpenIDLoginButton v-if="ssoProviders.includes('OpenID Connect')" @error="onError" @success="onSuccess" />
  </div>
</template>

<script lang="ts" setup>
import { authService, isTwoFactorChallengeRequired } from '@/services/authService'
import { logger } from '@/utils/logger'
import { useMessageToaster } from '@/composables/useMessageToaster'

import GoogleLoginButton from '@/components/auth/sso/GoogleLoginButton.vue'
import OpenIDLoginButton from '@/components/auth/sso/OpenIDLoginButton.vue'

const emit = defineEmits<{
  (e: 'loggedIn'): void
  (e: 'twoFactorRequired', loginToken: string): void
}>()

const { toastError } = useMessageToaster()

const ssoProviders = window.KOEL.sso_providers || []

const onError = (error: any) => {
  logger.error('SSO error: ', error)
  toastError('Login failed. Please try again.')
}

const onSuccess = (loginResponse: LoginResponse) => {
  if (isTwoFactorChallengeRequired(loginResponse)) {
    emit('twoFactorRequired', loginResponse.login_token)
    return
  }

  authService.setTokensUsingCompositeToken(loginResponse)
  authService.maybeRedirect()
  emit('loggedIn')
}
</script>
