<template>
  <div class="space-y-4">
    <WithGradientBorder
      v-for="{ id, component, color } in integrations"
      :key="id"
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
import type { Component } from 'vue'
import { Filter } from '@/config/hooks'
import { applyFilters } from '@/hooks'

import LastfmIntegration from '@/components/profile-preferences/LastfmIntegration.vue'
import ListenBrainzIntegration from '@/components/profile-preferences/ListenBrainzIntegration.vue'
import SpotifyIntegration from '@/components/profile-preferences/SpotifyIntegration.vue'
import MusicBrainzIntegration from '@/components/profile-preferences/MusicBrainzIntegration.vue'
import WithGradientBorder from '@/components/ui/WithGradientBorder.vue'

export interface ProfileIntegration {
  id: string
  component: Component
  color: string
}

const integrations = applyFilters<ProfileIntegration[]>(Filter.PROFILE_INTEGRATIONS, [
  { id: 'musicbrainz', component: MusicBrainzIntegration, color: '#ba478f' },
  { id: 'listenbrainz', component: ListenBrainzIntegration, color: '#eb743b' },
  { id: 'spotify', component: SpotifyIntegration, color: '#1db954' },
  { id: 'lastfm', component: LastfmIntegration, color: '#d31f27' },
])
</script>
