<template>
  <WithGradientBorder
    border-width="1px"
    :color="gradientColor"
    border-color="color-mix(in srgb, var(--color-fg), transparent 97%)"
    class="rounded-lg"
    :class="{ compact: layout === 'compact' }"
  >
    <article
      :class="layout"
      class="relative group flex h-full overflow-hidden rounded-[inherit] flex-col"
      data-testid="artist-album-card"
      :draggable="!isMobile.any"
      tabindex="0"
      @dblclick="onDblClick"
      @dragstart="onDragStart"
      @contextmenu.prevent="onContextMenu"
    >
      <div class="cover">
        <slot name="thumbnail">
          <Thumbnail v-if="hasThumbnail(entity)" :entity />
        </slot>
      </div>

      <footer class="relative z-10 flex flex-1 flex-col gap-1.5 overflow-hidden">
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
@reference '@css/app.pcss';
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

  :deep(:is(.thumbnail, .card-thumbnail)) {
    @apply rounded-none;
  }

  &.full {
    footer {
      @apply -mt-12 px-5 pb-5;
    }

    :deep(.cover-art) {
      mask-image: linear-gradient(to bottom, black 45%, transparent 100%);
    }
  }

  &.compact {
    @apply flex-row items-center min-h-24 rounded-md;

    .cover {
      @apply absolute inset-y-0 left-0 w-28;

      :deep(:is(.thumbnail, .card-thumbnail)) {
        @apply h-full aspect-auto;
      }
    }

    :deep(.cover-art) {
      mask-image: linear-gradient(to right, black 45%, transparent 100%);
    }

    footer {
      @apply ml-26 py-3 pr-3;
    }
  }
}

.compact {
  @apply max-w-full rounded-md;
}
</style>
