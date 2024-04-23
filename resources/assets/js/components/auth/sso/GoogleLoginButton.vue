<template>
  <button
    class="opacity-50 hover:opacity-100"
    title="Log in with Google"
    type="button"
    @click.prevent="loginWithGoogle"
  >
    <img :src="googleLogo" alt="Google Logo" height="32" width="32">
  </button>
</template>

<script lang="ts" setup>
import googleLogo from '@/../img/logos/google.svg'
import { openPopup } from '@/utils'

const emit = defineEmits<{
  (e: 'success', data: any): void
  (e: 'error', error: any): void
}>()

const loginWithGoogle = async () => {
  try {
    window.onmessage = (msg: MessageEvent) => emit('success', msg.data)
    openPopup('/auth/google/redirect', 'Google Login', 768, 640, window)
  } catch (error: unknown) {
    emit('error', error)
  }
}
</script>
