<template>
  <WithGradientBorder
    border-width="1px"
    color="var(--color-highlight)"
    border-color="color-mix(in srgb, var(--color-fg), transparent 97%)"
    class="rounded-lg max-w-full md:max-w-[256px]"
    :class="{ compact: layout === 'compact' }"
  >
    <article
      :class="layout"
      class="group relative flex p-5 rounded-[inherit] flex-col gap-5"
      data-testid="radio-station-card"
      tabindex="0"
      @dblclick="onDblClick"
      @contextmenu.prevent="onContextMenu"
    >
      <RadioStationThumbnail :station @clicked="onThumbnailClicked" />

      <footer class="flex flex-1 flex-col gap-1.5 overflow-hidden">
        <div class="name flex flex-col gap-2 whitespace-nowrap">
          <h3 class="font-medium text-k-fg">
            {{ station.name }}
            <FavoriteButton
              v-if="station.favorite"
              :favorite="station.favorite"
              class="ml-1"
              @toggle="toggleFavorite"
            />
          </h3>
        </div>
        <div class="meta text-[0.95rem] flex gap-1.5 text-k-fg-70">
          <p class="line-clamp-3" :title="station.description">{{ station.description }}</p>
        </div>
      </footer>
    </article>
  </WithGradientBorder>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'

import { radioStationStore } from '@/stores/radioStationStore'
import { playback } from '@/services/playbackManager'

import RadioStationThumbnail from '@/components/radio/RadioStationThumbnail.vue'
import WithGradientBorder from '@/components/ui/WithGradientBorder.vue'

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

const onDblClick = () => togglePlay()

const onThumbnailClicked = () => togglePlay()

const onContextMenu = (event: MouseEvent) =>
  openContextMenu<'RADIO_STATION'>(ContextMenu, event, {
    station: station.value,
  })

const toggleFavorite = () => radioStationStore.toggleFavorite(station.value)
</script>

<style lang="postcss" scoped>
article {
  @apply bg-k-fg-5;

  &.full {
    :deep(.play-icon) {
      @apply scale-[3];
    }
  }

  .name {
    a {
      @apply overflow-hidden text-ellipsis text-k-fg;

      &:is(:hover, :active, :focus) {
        @apply text-k-highlight;
      }
    }
  }

  &:focus,
  &:focus-within {
    @apply ring-1 ring-k-highlight;
  }

  &.compact {
    @apply flex-row gap-4 p-3 rounded-md items-center;

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
          @apply mr-0.5 content-['•'];
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

.compact {
  @apply max-w-full rounded-md;
}
</style>
