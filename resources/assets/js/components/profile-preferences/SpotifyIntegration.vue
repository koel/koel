<template>
  <section class="text-secondary">
    <h1>
      <span class="spotify-icon">
        <Icon :icon="faSpotify" />
      </span>
      Spotify Integration
    </h1>

    <div v-if="useSpotify">
      <p>
        Spotify integration is enabled.
        Koel will attempt to retrieve album arts and artist images from Spotify when a song is played, if needed.
      </p>
    </div>
    <div v-else>
      <p>
        Spotify integration is not enabled.
        <span v-if="isAdmin" data-testid="spotify-admin-instruction">
          Check
          <a href="https://docs.koel.dev/service-integrations#spotify" class="text-highlight" target="_blank">
            Documentation
          </a>
          for instructions.
        </span>
      </p>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { faSpotify } from '@fortawesome/free-brands-svg-icons'
import { useAuthorization, useThirdPartyServices } from '@/composables';

const { currentUser, isAdmin } = useAuthorization();
const { useSpotify } = useThirdPartyServices();
</script>

<style scoped lang="scss">
.spotify-icon {
  margin-right: .4rem;
  color: #1db954; // Spotify green
}
</style>
