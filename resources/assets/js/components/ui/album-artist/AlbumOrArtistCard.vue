<template>
  <article
    :class="layout"
    class="relative flex max-w-full md:max-w-[256px] border p-5 rounded-lg flex-col gap-5 transition border-color duration-200"
    data-testid="artist-album-card"
    draggable="true"
    tabindex="0"
    @dblclick="onDblClick"
    @dragstart="onDragStart"
    @contextmenu.prevent="onContextMenu"
  >
    <Thumbnail :entity="entity" />

    <footer class="flex flex-1 flex-col gap-1.5 overflow-hidden">
      <div class="name flex flex-col gap-2 whitespace-nowrap">
        <slot name="name" />
      </div>
      <p class="meta text-[0.9rem] flex gap-1.5 opacity-70 hover:opacity-100">
        <slot name="meta" />
      </p>
    </footer>
  </article>
</template>

<script lang="ts" setup>
import Thumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import { toRefs } from 'vue'

const props = withDefaults(defineProps<{ layout?: ArtistAlbumCardLayout, entity: Artist | Album }>(), {
  layout: 'full'
})

const { layout } = toRefs(props)

const emit = defineEmits<{
  (e: 'dblclick'): void,
  (e: 'dragstart', event: DragEvent): void,
  (e: 'contextmenu', event: MouseEvent): void
}>()

const onDblClick = () => emit('dblclick')
const onDragStart = (e: DragEvent) => emit('dragstart', e)
const onContextMenu = (e: MouseEvent) => emit('contextmenu', e)
</script>

<style lang="postcss" scoped>
article {
  @apply bg-k-bg-secondary border border-k-border hover:border-white/15;

  .name {
    &:deep(a) {
      @apply overflow-hidden text-ellipsis text-k-text-primary;
    }

    &:deep(a:hover), &:deep(a:active), &:deep(a:focus) {
      @apply text-k-accent;
    }
  }

  &:focus, &:focus-within {
    @apply ring-1 ring-k-accent;
  }

  &.compact {
    @apply flex-row gap-4 max-w-full p-3 rounded-md items-center;

    .thumbnail {
      @apply w-[80px] rounded-md;
    }
  }

  .meta {
    :deep(a) {
      & + a {
        &::before {
          @apply mr-0.5 content-['•'] text-k-text-secondary;
        }
      }
    }
  }
}
</style>
