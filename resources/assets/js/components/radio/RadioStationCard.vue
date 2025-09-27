<template>
  <article
    :class="layout"
    class="relative group flex max-w-full md:max-w-[256px] border p-5 rounded-lg flex-col gap-5"
    data-testid="radio-station-card"
    tabindex="0"
    @dblclick="onDblClick"
    @contextmenu.prevent="onContextMenu"
  >
    <RadioStationThumbnail :station @clicked="onThumbnailClicked" />

    <footer class="flex flex-1 flex-col gap-1.5 overflow-hidden">
      <div class="name flex flex-col gap-2 whitespace-nowrap">
        <h3 class="font-medium">
          {{ station.name }}
          <FavoriteButton v-if="station.favorite" :favorite="station.favorite" class="ml-1" @toggle="toggleFavorite" />
        </h3>
      </div>
      <div class="meta text-[0.9rem] flex gap-1.5 opacity-70 hover:opacity-100">
        <p class="line-clamp-3" :title="station.description">{{ station.description }}</p>
      </div>
    </footer>
  </article>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'

import { radioStationStore } from '@/stores/radioStationStore'
import { playback } from '@/services/playbackManager'

import RadioStationThumbnail from '@/components/radio/RadioStationThumbnail.vue'

const props = withDefaults(defineProps<{ layout?: CardLayout, station: RadioStation }>(), {
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

const onDblClick = () => togglePlay()

const onThumbnailClicked = () => togglePlay()

const onContextMenu = (event: MouseEvent) => openContextMenu<'RADIO_STATION'>(ContextMenu, event, {
  station: station.value,
})

const toggleFavorite = () => radioStationStore.toggleFavorite(station.value)
</script>

<style lang="postcss" scoped>
article {
  @apply bg-k-bg-secondary border border-k-border hover:border-white/15;

  &.full {
    :deep(.play-icon) {
      @apply scale-[3];
    }
  }

  .name {
    a {
      @apply overflow-hidden text-ellipsis text-k-text-primary;

      &:is(:hover, :active, :focus) {
        @apply text-k-accent;
      }
    }
  }

  &:focus,
  &:focus-within {
    @apply ring-1 ring-k-accent;
  }

  &.compact {
    @apply flex-row gap-4 max-w-full p-3 rounded-md items-center;

    .thumbnail {
      @apply w-[80px] rounded-md;
    }
  }

  .meta {
    :deep(a),
    :deep(button) {
      & + a,
      & + button {
        &::before {
          @apply mr-0.5 content-['•'] text-k-text-secondary;
        }
      }

      & + button {
        &::before {
          @apply mr-1;
        }
      }
    }
  }
}
</style>
