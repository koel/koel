<template>
  <section>
    <h3 class="text-2xl mb-2 flex items-center gap-2">
      <span class="mr-2 text-[#1db954]">
        <img :src="musicbrainzLogo" alt="MusicBrainz Logo" height="20" width="20">
      </span>
      {{ t('integrations.musicbrainz.title') }}
    </h3>

    <div v-if="useMusicBrainz">
      <p>
        {{ t('integrations.musicbrainz.enabled') }}
        {{ t('integrations.musicbrainz.enabledDescription', { appName }) }}
      </p>
    </div>
    <div v-else>
      <p>
        {{ t('integrations.musicbrainz.notEnabled') }}
        <span v-if="currentUserCan.manageSettings()" data-testid="spotify-admin-instruction">
          {{ t('integrations.musicbrainz.checkDocumentation') }}
          <a href="https://docs.koel.dev/service-integrations#musicbrainz-wikipedia" target="_blank">{{ t('integrations.musicbrainz.documentation') }}</a>
          {{ t('integrations.musicbrainz.forInstructions') }}
        </span>
      </p>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import musicbrainzLogo from '@/../img/logos/musicbrainz.svg'

import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { usePolicies } from '@/composables/usePolicies'
import { useBranding } from '@/composables/useBranding'

const { t } = useI18n()
const { currentUserCan } = usePolicies()
const { useMusicBrainz } = useThirdPartyServices()
const { name: appName } = useBranding()
</script>
