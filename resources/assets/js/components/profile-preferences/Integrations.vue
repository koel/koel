<template>
  <div class="space-y-5">
    <MusicBrainzIntegration />
    <SpotifyIntegration />
    <LastfmIntegration />
  </div>
</template>

<script lang="ts" setup>
import LastfmIntegration from '@/components/profile-preferences/LastfmIntegration.vue'
import SpotifyIntegration from '@/components/profile-preferences/SpotifyIntegration.vue'
import MusicBrainzIntegration from '@/components/profile-preferences/MusicBrainzIntegration.vue'
</script>
