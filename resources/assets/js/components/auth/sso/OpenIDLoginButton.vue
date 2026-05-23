<template>
  <button
    class="opacity-70 hover:opacity-100 flex items-center gap-2 px-3 py-2 border border-k-fg-20 rounded-sm"
    type="button"
    @click.prevent="loginWithOpenID"
  >
    <img v-if="brandIconUrl" :src="brandIconUrl" :alt="label" class="w-4 h-4" @error="brandIconUrl = ''" />
    <Icon v-else :icon="faKey" />
    <span class="text-sm">{{ label }}</span>
  </button>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { faKey } from '@fortawesome/free-solid-svg-icons'
import { openPopup } from '@/utils/helpers'

const label = window.KOEL.sso_oidc_label || 'OpenID Connect'

const brandSlugs: Array<[RegExp, string]> = [
  [/authentik/i, 'authentik'],
  [/authelia/i, 'authelia'],
  [/keycloak/i, 'keycloak'],
  [/zitadel/i, 'zitadel'],
  [/okta/i, 'okta'],
  [/auth0/i, 'auth0'],
  [/github/i, 'github'],
  [/gitlab/i, 'gitlab'],
  [/microsoft|azure|entra/i, 'microsoft'],
  [/apple/i, 'apple'],
  [/cognito|amazon|aws/i, 'aws'],
]

const matchedSlug = brandSlugs.find(([pattern]) => pattern.test(label))?.[1]
const brandIconUrl = ref(
  matchedSlug ? `https://cdn.jsdelivr.net/gh/homarr-labs/dashboard-icons/svg/${matchedSlug}.svg` : '',
)

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
