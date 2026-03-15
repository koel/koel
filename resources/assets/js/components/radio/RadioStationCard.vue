<template>
  <BaseCard
    :entity="station"
    :layout="layout"
    :title="station.name"
    @contextmenu="onContextMenu"
    @dblclick="togglePlay"
  >
    <template #thumbnail>
      <RadioStationThumbnail :station @clicked="togglePlay" />
    </template>
    <template #name>
      <h3 class="font-medium text-k-fg">
        {{ station.name }}
        <FavoriteButton v-if="station.favorite" :favorite="station.favorite" class="ml-1" @toggle="toggleFavorite" />
      </h3>
    </template>
    <template #meta>
      <span class="line-clamp-3" :title="station.description">{{ station.description }}</span>
    </template>
  </BaseCard>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'
import { radioStationStore } from '@/stores/radioStationStore'
import { playback } from '@/services/playbackManager'

import BaseCard from '@/components/ui/album-artist/AlbumOrArtistCard.vue'
import RadioStationThumbnail from '@/components/radio/RadioStationThumbnail.vue'

const props = withDefaults(defineProps<{ layout?: CardLayout; station: RadioStation }>(), {
  layout: 'full',
})
const FavoriteButton = defineAsyncComponent(() => import('@/components/ui/FavoriteButton.vue'))
const ContextMenu = defineAsyncComponent(() => import('@/components/radio/RadioStationContextMenu.vue'))

const { layout, station } = toRefs(props)

const { openContextMenu } = useContextMenu()

const togglePlay = () => {
  if (station.value.playback_state === 'Playing') {
    playback('radio').stop()
  } else {
    playback('radio').play(station.value)
  }
}

const onContextMenu = (event: MouseEvent) =>
  openContextMenu<'RADIO_STATION'>(ContextMenu, event, {
    station: station.value,
  })

const toggleFavorite = () => radioStationStore.toggleFavorite(station.value)
</script>
