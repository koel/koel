<template>
  <button
    class="opacity-70 hover:opacity-100 flex items-center gap-2 px-3 py-2 border border-k-fg-20 rounded-sm"
    type="button"
    @click.prevent="loginWithOpenID"
  >
    <img v-if="brandIconUrl" :src="brandIconUrl" :alt="label" class="w-4 h-4" />
    <Icon v-else :icon="faKey" />
    <span class="text-sm">{{ label }}</span>
  </button>
</template>

<script lang="ts" setup>
import { faKey } from '@fortawesome/free-solid-svg-icons'
import { openPopup } from '@/utils/helpers'

import appleLogo from '@/../img/logos/sso/apple.svg'
import auth0Logo from '@/../img/logos/sso/auth0.svg'
import autheliaLogo from '@/../img/logos/sso/authelia.svg'
import authentikLogo from '@/../img/logos/sso/authentik.svg'
import awsLogo from '@/../img/logos/sso/aws.svg'
import githubLogo from '@/../img/logos/sso/github.svg'
import gitlabLogo from '@/../img/logos/sso/gitlab.svg'
import keycloakLogo from '@/../img/logos/sso/keycloak.svg'
import microsoftLogo from '@/../img/logos/sso/microsoft.svg'
import oktaLogo from '@/../img/logos/sso/okta.svg'
import zitadelLogo from '@/../img/logos/sso/zitadel.svg'

const label = window.KOEL.sso_oidc_label || 'OpenID Connect'

const brandIcons: Array<[RegExp, string]> = [
  [/authentik/i, authentikLogo],
  [/authelia/i, autheliaLogo],
  [/keycloak/i, keycloakLogo],
  [/zitadel/i, zitadelLogo],
  [/okta/i, oktaLogo],
  [/auth0/i, auth0Logo],
  [/github/i, githubLogo],
  [/gitlab/i, gitlabLogo],
  [/microsoft|azure|entra/i, microsoftLogo],
  [/apple/i, appleLogo],
  [/cognito|amazon|aws/i, awsLogo],
]

const brandIconUrl = brandIcons.find(([pattern]) => pattern.test(label))?.[1] ?? ''

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
