<template>
  <button class="opacity-50 hover:opacity-100" type="button" @click.prevent="loginWithGoogle">
    <img :src="googleLogo" alt="" height="32" width="32" />
    <span class="sr-only">Log in with Google</span>
  </button>
</template>

<script lang="ts" setup>
import googleLogo from '@/../img/logos/google.svg'
import { useSsoLogin } from '@/composables/useSsoLogin'

const emit = defineEmits<{
  (e: 'success', data: any): void
  (e: 'error', error: any): void
}>()

const { startSsoLogin } = useSsoLogin()

const loginWithGoogle = async () => {
  try {
    startSsoLogin('/auth/google/redirect', 'Google Login', token => emit('success', token))
  } catch (error: unknown) {
    emit('error', error)
  }
}
</script>
