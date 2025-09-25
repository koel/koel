<template>
  <section class="text-k-text-secondary">
    <h3 class="text-2xl mb-2">
      <span class="mr-2 text-[#1db954]">
        <Icon :icon="faSpotify" />
      </span>
      Spotify Integration
    </h3>

    <div v-if="useSpotify">
      <p>
        Spotify integration is enabled.
        Koel will attempt to retrieve album arts and artist images from Spotify when a song is played, if needed.
      </p>
    </div>
    <div v-else>
      <p>
        Spotify integration is not enabled.
        <span v-if="currentUserCan.manageSettings()" data-testid="spotify-admin-instruction">
          Check
          <a href="https://docs.koel.dev/service-integrations#spotify" target="_blank">Documentation</a>
          for integration instructions.
        </span>
      </p>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { faSpotify } from '@fortawesome/free-brands-svg-icons'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { usePolicies } from '@/composables/usePolicies'

const { currentUserCan } = usePolicies()
const { useSpotify } = useThirdPartyServices()
</script>
