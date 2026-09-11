<template>
  <div class="space-y-4">
    <WithGradientBorder
      v-for="{ component, color } in integrations"
      :key="color"
      :color
      border-color="color-mix(in srgb, var(--color-fg), transparent 97%)"
      border-width="1px"
      class="rounded-lg"
    >
      <div class="bg-k-fg-5 p-5 rounded-[inherit]">
        <component :is="component" />
      </div>
    </WithGradientBorder>
  </div>
</template>

<script lang="ts" setup>
import LastfmIntegration from '@/components/profile-preferences/LastfmIntegration.vue'
import ListenbrainzIntegration from '@/components/profile-preferences/ListenbrainzIntegration.vue'
import SpotifyIntegration from '@/components/profile-preferences/SpotifyIntegration.vue'
import MusicBrainzIntegration from '@/components/profile-preferences/MusicBrainzIntegration.vue'
import WithGradientBorder from '@/components/ui/WithGradientBorder.vue'

const integrations = [
  { component: MusicBrainzIntegration, color: '#ba478f' },
  { component: ListenbrainzIntegration, color: '#eb743b' },
  { component: SpotifyIntegration, color: '#1db954' },
  { component: LastfmIntegration, color: '#d31f27' },
]
</script>
