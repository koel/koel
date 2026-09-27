<template>
  <SettingGroup>
    <template #title>Services</template>
    <template #subtitle>Services {{ appName }} uses for everyone on this installation.</template>

    <ul class="divide-y divide-k-fg-10">
      <li
        v-for="service in services"
        :key="service.id"
        :data-enabled="service.enabled"
        :data-testid="`service-${service.id}`"
        class="flex items-center justify-between gap-4 py-2 first:pt-0 last:pb-0"
      >
        <span>{{ service.name }}</span>
        <span v-if="service.enabled" class="text-k-success">Enabled</span>
        <span v-else class="text-k-fg-50">
          Not enabled
          <template v-if="service.docsUrl">
            ·
            <a :href="service.docsUrl" target="_blank">Set up</a>
          </template>
        </span>
      </li>
    </ul>
  </SettingGroup>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useBranding } from '@/composables/useBranding'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'

import SettingGroup from '@/components/screens/settings/SettingGroup.vue'

const { name: appName } = useBranding()
const { isPlus } = useKoelPlus()
const { useMusicBrainz, useLastfm, useSpotify, useYouTube, useAppleMusic, useTicketmaster } = useThirdPartyServices()

const DOCS_URL = 'https://docs.koel.dev'

const services = computed(() => [
  {
    id: 'musicbrainz',
    name: 'MusicBrainz',
    enabled: useMusicBrainz.value,
    docsUrl: `${DOCS_URL}/service-integrations#musicbrainz-wikipedia`,
  },
  { id: 'lastfm', name: 'Last.fm', enabled: useLastfm.value, docsUrl: `${DOCS_URL}/service-integrations#last-fm` },
  { id: 'spotify', name: 'Spotify', enabled: useSpotify.value, docsUrl: `${DOCS_URL}/service-integrations#spotify` },
  { id: 'youtube', name: 'YouTube', enabled: useYouTube.value, docsUrl: `${DOCS_URL}/service-integrations#youtube` },
  { id: 'apple-music', name: 'Apple Music', enabled: useAppleMusic.value, docsUrl: null },
  ...(isPlus.value
    ? [
        {
          id: 'ticketmaster',
          name: 'Ticketmaster',
          enabled: useTicketmaster.value,
          docsUrl: `${DOCS_URL}/plus/ticketmaster`,
        },
      ]
    : []),
])
</script>
