<template>
  <div class="space-y-4">
    <WithGradientBorder
      v-for="service in services"
      :key="service.id"
      :color="service.color"
      border-color="color-mix(in srgb, var(--color-fg), transparent 97%)"
      border-width="1px"
      class="rounded-lg"
    >
      <section
        :data-enabled="service.enabled"
        :data-testid="`service-${service.id}`"
        class="bg-k-fg-5 p-5 rounded-[inherit]"
      >
        <h3 class="text-2xl leading-none mb-3 flex items-center gap-2">
          <span :style="{ color: service.color }" class="mr-2">
            <img v-if="service.logo" :alt="`${service.name} logo`" :src="service.logo" height="20" width="20" />
            <Icon v-else :icon="service.icon" />
          </span>
          {{ service.name }}
          <span v-if="service.enabled" class="badge bg-k-success text-white">Enabled</span>
          <span v-else class="badge bg-k-fg-10 text-k-fg-70">Disabled</span>
        </h3>

        <p>
          {{ service.description }}
          <a :href="service.docsUrl" class="text-k-highlight hover:text-k-fg" target="_blank">Documentation</a>
        </p>
      </section>
    </WithGradientBorder>
  </div>
</template>

<script lang="ts" setup>
import { faLastfm, faSpotify, faYoutube } from '@fortawesome/free-brands-svg-icons'
import { computed } from 'vue'
import musicbrainzLogo from '@/../img/logos/musicbrainz.svg'
import ticketmasterLogo from '@/../img/logos/ticketmaster.png'
import { useBranding } from '@/composables/useBranding'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'

import WithGradientBorder from '@/components/ui/WithGradientBorder.vue'

const { name: appName } = useBranding()
const { isPlus } = useKoelPlus()
const { useMusicBrainz, useLastfm, useSpotify, useYouTube, useTicketmaster } = useThirdPartyServices()

const DOCS_URL = 'https://docs.koel.dev'

const allServices = computed(() => [
  {
    id: 'musicbrainz',
    name: 'MusicBrainz',
    logo: musicbrainzLogo,
    color: '#ba478f',
    enabled: useMusicBrainz.value,
    description: `Fills in album and artist information, artist images and album covers from MusicBrainz, Wikipedia and the Cover Art Archive.`,
    docsUrl: `${DOCS_URL}/service-integrations#musicbrainz-wikipedia`,
  },
  {
    id: 'lastfm',
    name: 'Last.fm',
    icon: faLastfm,
    color: '#d31f27',
    enabled: useLastfm.value,
    description: `Fills in album and artist details from Last.fm, and lets each user connect their Last.fm account to scrobble what they play in ${appName}.`,
    docsUrl: `${DOCS_URL}/service-integrations#last-fm`,
  },
  {
    id: 'spotify',
    name: 'Spotify',
    icon: faSpotify,
    color: '#1db954',
    enabled: useSpotify.value,
    description: `Fetches album covers and artist images from Spotify.`,
    docsUrl: `${DOCS_URL}/service-integrations#spotify`,
  },
  {
    id: 'youtube',
    name: 'YouTube',
    icon: faYoutube,
    color: '#ff0033',
    enabled: useYouTube.value,
    description: `Shows YouTube videos related to the song being played, to watch without leaving ${appName}.`,
    docsUrl: `${DOCS_URL}/service-integrations#youtube`,
  },
  ...(isPlus.value
    ? [
        {
          id: 'ticketmaster',
          name: 'Ticketmaster',
          logo: ticketmasterLogo,
          color: '#026cdf',
          enabled: useTicketmaster.value,
          description: `Lists an artist's upcoming concerts from Ticketmaster on the artist's page, with links to buy tickets.`,
          docsUrl: `${DOCS_URL}/plus/ticketmaster`,
        },
      ]
    : []),
])

const services = computed(() =>
  [...allServices.value].sort((first, second) => Number(second.enabled) - Number(first.enabled)),
)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.badge {
  @apply rounded-full px-2 py-1 text-[.8rem] font-normal leading-none;
}
</style>
