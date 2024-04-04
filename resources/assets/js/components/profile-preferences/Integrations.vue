<template>
  <div class="integrations">
    <article>
      <SpotifyIntegration />
    </article>
    <article>
      <LastfmIntegration />
    </article>
  </div>
</template>

<script setup lang="ts">

import LastfmIntegration from '@/components/profile-preferences/LastfmIntegration.vue'
import SpotifyIntegration from '@/components/profile-preferences/SpotifyIntegration.vue'
</script>

<style scoped lang="postcss">
.integrations {
  > article + article {
    margin-top: 2rem;
  }

  :deep(h3) {
    font-size: 1.8rem;
    margin-bottom: 1rem;
  }
}
</style>
