<template>
  <section>
    <h3 class="text-2xl mb-2">
      <span class="mr-2 text-[#1db954]">
        <Icon :icon="faSpotify" />
      </span>
      {{ t('integrations.spotify.title') }}
    </h3>

    <div v-if="useSpotify">
      <p>
        {{ t('integrations.spotify.enabled') }}
        {{ t('integrations.spotify.enabledDescription', { appName }) }}
      </p>
    </div>
    <div v-else>
      <p>
        {{ t('integrations.spotify.notEnabled') }}
        <span v-if="currentUserCan.manageSettings()" data-testid="spotify-admin-instruction">
          {{ t('integrations.spotify.checkDocumentation') }}
          <a href="https://docs.koel.dev/service-integrations#spotify" target="_blank">{{ t('integrations.spotify.documentation') }}</a>
          {{ t('integrations.spotify.forInstructions') }}
        </span>
      </p>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import { faSpotify } from '@fortawesome/free-brands-svg-icons'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { usePolicies } from '@/composables/usePolicies'
import { useBranding } from '@/composables/useBranding'

const { t } = useI18n()
const { currentUserCan } = usePolicies()
const { useSpotify } = useThirdPartyServices()
const { name: appName } = useBranding()
</script>
