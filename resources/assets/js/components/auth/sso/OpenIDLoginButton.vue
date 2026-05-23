<template>
  <button
    class="opacity-50 hover:opacity-100 flex items-center gap-2 px-3 py-2 border border-k-fg-20 rounded-sm"
    type="button"
    @click.prevent="loginWithOpenID"
  >
    <Icon :icon="faKey" />
    <span class="text-sm">{{ label }}</span>
  </button>
</template>

<script lang="ts" setup>
import { faKey } from '@fortawesome/free-solid-svg-icons'
import { openPopup } from '@/utils/helpers'

const label = window.KOEL.sso_oidc_label || 'OpenID Connect'

const emit = defineEmits<{
  (e: 'success', data: any): void
  (e: 'error', error: any): void
}>()

const loginWithOpenID = async () => {
  try {
    window.onmessage = (msg: MessageEvent) => emit('success', msg.data)
    openPopup('/auth/oidc/redirect', 'OpenID Login', 768, 640, window)
  } catch (error: unknown) {
    emit('error', error)
  }
}
</script>
