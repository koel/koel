<template>
  <WithGradientBorder
    border-width="1px"
    :color="gradientColor"
    border-color="color-mix(in srgb, var(--color-fg), transparent 97%)"
    class="rounded-xl"
    :class="{ compact: layout === 'compact' }"
  >
    <article
      :class="[layout, { tinted: coverBackgroundColor }]"
      :style="{ backgroundColor: coverBackgroundColor }"
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

      <div v-if="layout === 'full'" class="shade pointer-events-none absolute inset-0" />

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
import { computed, ref, toRefs, watch } from 'vue'
import { getCoverBackgroundColor } from '@/utils/coverColor'
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

const coverUrl = computed(() => {
  switch (props.entity.type) {
    case 'albums':
      return props.entity.cover
    case 'radio-stations':
      return props.entity.logo
    case 'artists':
      return props.entity.image || props.entity.album_cover
    default:
      return props.entity.image
  }
})

const coverBackgroundColor = ref<string>()

watch(
  [coverUrl, layout],
  async ([url, currentLayout]) => {
    coverBackgroundColor.value = undefined

    if (!url || currentLayout !== 'full') {
      return
    }

    const color = await getCoverBackgroundColor(url)

    if (url === coverUrl.value) {
      coverBackgroundColor.value = color ?? undefined
    }
  },
  { immediate: true },
)

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
    @apply aspect-[3/4] justify-end text-white;

    .cover {
      @apply absolute inset-x-0 top-0;
    }

    :deep(:is(.cover-art, .overlay)) {
      mask-image: linear-gradient(
        to bottom,
        black 45%,
        rgb(0 0 0 / 0.85) 60%,
        rgb(0 0 0 / 0.55) 75%,
        rgb(0 0 0 / 0.2) 90%,
        transparent 100%
      );
    }

    .shade {
      background:
        linear-gradient(
          to top,
          rgb(0 0 0 / 0.8) 0%,
          rgb(0 0 0 / 0.72) 12%,
          rgb(0 0 0 / 0.55) 25%,
          rgb(0 0 0 / 0.35) 38%,
          rgb(0 0 0 / 0.18) 50%,
          rgb(0 0 0 / 0.06) 62%,
          transparent 75%
        ),
        linear-gradient(to bottom, rgb(0 0 0 / 0.35) 0%, rgb(0 0 0 / 0.15) 12%, rgb(0 0 0 / 0.04) 24%, transparent 35%);
    }

    &.tinted .shade {
      @apply opacity-50;
    }

    footer {
      @apply flex-none px-5 pb-5;
    }

    .name:deep(a) {
      @apply text-white;
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

    :deep(:is(.cover-art, .overlay)) {
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
