<template>
  <section class="text-k-text-secondary">
    <h3 class="text-2xl mb-2 flex items-center gap-2">
      <span class="mr-2 text-[#1db954]">
        <img :src="musicbrainzLogo" alt="MusicBrainz Logo" height="20" width="20">
      </span>
      MusicBrainz Integration
    </h3>

    <div v-if="useMusicBrainz">
      <p>
        MusicBrainz integration is enabled.
        Koel will attempt to retrieve album and artist information from MusicBrainz (and Wikipedia) when a song is
        played, if needed.
      </p>
    </div>
    <div v-else>
      <p>
        MusicBrainz integration is not enabled.
        <span v-if="isAdmin" data-testid="spotify-admin-instruction">
          Check
          <a href="https://docs.koel.dev/service-integrations#musicbrainz-wikipedia" target="_blank">Documentation</a>
          for integration instructions.
        </span>
      </p>
    </div>
  </section>
</template>

<script lang="ts" setup>
import musicbrainzLogo from '@/../img/logos/musicbrainz.svg'

import { useAuthorization } from '@/composables/useAuthorization'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'

const { isAdmin } = useAuthorization()
const { useMusicBrainz } = useThirdPartyServices()
</script>
