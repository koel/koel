<template>
  <div class="space-y-5">
    <article>
      <SpotifyIntegration />
    </article>
    <article>
      <LastfmIntegration />
    </article>
  </div>
</template>

<script lang="ts" setup>
import LastfmIntegration from '@/components/profile-preferences/LastfmIntegration.vue'
import SpotifyIntegration from '@/components/profile-preferences/SpotifyIntegration.vue'
</script>
