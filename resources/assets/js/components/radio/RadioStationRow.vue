<template>
  <article
    class="radio-station-row group pl-5 flex items-center h-[64px] border-b border-k-fg-10 hover:bg-k-fg-5 transition-colors"
    data-testid="radio-station-row"
    @contextmenu.prevent="onContextMenu"
    @dblclick.prevent.stop="togglePlay"
  >
    <span class="name flex gap-3 items-center min-w-0">
      <span
        :style="{ backgroundImage: `url(${defaultCover})` }"
        class="w-[48px] aspect-square rounded-sm bg-cover bg-center flex-none overflow-hidden"
      >
        <img v-if="station.logo" :src="station.logo" alt="" class="w-full aspect-square object-cover" loading="lazy" />
      </span>
      <span class="truncate" :title="station.name">{{ station.name }}</span>
    </span>
    <span v-if="shouldShowColumn('description')" class="description truncate" :title="station.description">
      {{ station.description }}
    </span>
    <span v-if="shouldShowColumn('created_at')" class="created-at text-k-fg-50">{{ formatCreatedAt }}</span>
    <span v-if="shouldShowColumn('favorite')" class="favorite">
      <FavoriteButton :favorite="station.favorite" @toggle="emit('toggle-favorite', station)" />
    </span>
    <span class="extra">
      <button class="text-k-fg-50 hover:text-k-fg p-1" title="More actions" @click="onContextMenu">
        <Icon :icon="faEllipsis" />
      </button>
    </span>
  </article>
</template>

<script lang="ts" setup>
import { faEllipsis } from '@fortawesome/free-solid-svg-icons'
import { computed } from 'vue'
import { useContextMenu } from '@/composables/useContextMenu'
import { useBranding } from '@/composables/useBranding'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { radioStationTableColumnConfig } from '@/config/tables'
import { playback } from '@/services/playbackManager'
import { defineAsyncComponent } from '@/utils/helpers'

import FavoriteButton from '@/components/ui/FavoriteButton.vue'

const props = defineProps<{ station: RadioStation }>()

const emit = defineEmits<{
  (e: 'toggle-favorite', station: RadioStation): void
}>()

const ContextMenu = defineAsyncComponent(() => import('@/components/radio/RadioStationContextMenu.vue'))

const { openContextMenu } = useContextMenu()
const { cover: defaultCover } = useBranding()
const { shouldShowColumn } = useTableColumnVisibility(radioStationTableColumnConfig)

const formatCreatedAt = computed(() =>
  props.station.created_at ? new Date(props.station.created_at).toLocaleDateString() : '—',
)

const onContextMenu = (event: MouseEvent) =>
  openContextMenu<'RADIO_STATION'>(ContextMenu, event, { station: props.station })

const togglePlay = () => {
  if (props.station.playback_state === 'Playing') {
    playback('radio').stop()
  } else {
    playback('radio').play(props.station)
  }
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.radio-station-row > span {
  @apply text-left p-2 align-middle truncate;

  &.name {
    @apply flex-1 min-w-0 flex items-center;
  }

  &.description {
    @apply flex-[2_1_0%] min-w-0 text-k-fg-70;
  }

  &.created-at {
    @apply basis-32;
  }

  &.favorite {
    @apply basis-16 text-center;
  }

  &.extra {
    @apply basis-12 text-center;
  }
}
</style>
