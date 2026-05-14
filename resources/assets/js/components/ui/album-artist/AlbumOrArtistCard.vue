<template>
  <WithGradientBorder
    border-width="1px"
    :color="gradientColor"
    border-color="color-mix(in srgb, var(--color-fg), transparent 97%)"
    class="rounded-lg max-w-full md:max-w-[256px]"
    :class="{ compact: layout === 'compact' }"
  >
    <article
      :class="layout"
      class="relative group flex p-5 rounded-[inherit] flex-col gap-5"
      data-testid="artist-album-card"
      :draggable="!isMobile.any"
      tabindex="0"
      @dblclick="onDblClick"
      @dragstart="onDragStart"
      @contextmenu.prevent="onContextMenu"
    >
      <slot name="thumbnail">
        <Thumbnail v-if="hasThumbnail(entity)" :entity />
      </slot>

      <footer class="flex flex-1 flex-col gap-1.5 overflow-hidden">
        <div class="name flex flex-col gap-2 whitespace-nowrap">
          <slot name="name" />
        </div>
        <p class="meta text-[0.9rem] flex gap-1.5 opacity-70 hover:opacity-100">
          <slot name="meta" />
        </p>
      </footer>

      <slot />
    </article>
  </WithGradientBorder>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { computed, toRefs } from 'vue'
import { textToHsl } from '@/utils/formatters'

import Thumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import WithGradientBorder from '@/components/ui/WithGradientBorder.vue'

const props = withDefaults(defineProps<{ layout?: CardLayout; entity: Artist | Album | Podcast | RadioStation }>(), {
  layout: 'full',
})

const emit = defineEmits<{
  (e: 'dblclick'): void
  (e: 'dragstart', event: DragEvent): void
  (e: 'contextmenu', event: MouseEvent): void
}>()

const hasThumbnail = (entity: Artist | Album | Podcast | RadioStation): entity is Artist | Album =>
  entity.type !== 'radio-stations' && entity.type !== 'podcasts'

const { layout } = toRefs(props)
const gradientColor = computed(() => textToHsl(String(props.entity.id)))

const onDblClick = () => emit('dblclick')
const onDragStart = (e: DragEvent) => emit('dragstart', e)
const onContextMenu = (e: MouseEvent) => emit('contextmenu', e)
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
    &:deep(a) {
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

    :deep(.thumbnail) {
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
