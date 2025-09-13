<template>
  <button
    class="opacity-50 hover:opacity-100"
    title="Log in with Authelia"
    type="button"
    @click.prevent="loginWithAuthelia"
  >
    <img :src="autheliaLogo" alt="Authelia Logo" height="32" width="32">
  </button>
</template>

<script lang="ts" setup>
import autheliaLogo from '@/../img/logos/authelia.svg'
import { openPopup } from '@/utils/helpers'

const emit = defineEmits<{
  (e: 'success', data: any): void
  (e: 'error', error: any): void
}>()

const loginWithAuthelia = async () => {
  try {
    window.onmessage = (msg: MessageEvent) => emit('success', msg.data)
    openPopup('/auth/authelia/redirect', 'Authelia Login', 768, 640, window)
  } catch (error: unknown) {
    emit('error', error)
  }
}
</script>
