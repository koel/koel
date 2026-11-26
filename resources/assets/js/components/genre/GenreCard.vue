<template>
  <li
    :data-plain="isPlain || undefined"
    :data-size="size"
    class="genre-card"
    draggable="true"
    tabindex="0"
    @dragstart="onDragStart"
    @contextmenu.prevent="onContextMenu"
  >
    <a
      :href="url('genres.show', { id: genre.id })"
      :style="{ '--genre-color': genreColor }"
      :title="genre.name || 'No Genre'"
      class="inline-flex items-baseline gap-2 rounded-full whitespace-nowrap text-k-fg-90 hover:text-k-fg transition active:scale-95 active:transition-none"
    >
      <span :class="genre.name || 'italic'">{{ genre.name || 'No Genre' }}</span>
      <span class="count text-k-fg-50 tabular-nums">{{ genre.song_count.toLocaleString() }}</span>
    </a>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { textToHsl } from '@/utils/formatters'
import type { GenreSize } from '@/utils/genreSize'
import { useRouter } from '@/composables/useRouter'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useContextMenu } from '@/composables/useContextMenu'
import { defineAsyncComponent } from '@/utils/helpers'

const props = defineProps<{ genre: Genre; size: GenreSize }>()

const ContextMenu = defineAsyncComponent(() => import('@/components/genre/GenreContextMenu.vue'))

const { url } = useRouter()
const { startDragging } = useDraggable('genre')
const { openContextMenu } = useContextMenu()

const isPlain = computed(() => !props.genre.name || props.size === 0)

const genreColor = computed(() => (isPlain.value ? 'var(--color-fg)' : textToHsl(props.genre.id)))

const onContextMenu = (event: MouseEvent) =>
  openContextMenu<'GENRE'>(ContextMenu, event, {
    genre: props.genre,
  })

const onDragStart = (event: DragEvent) => startDragging(event, props.genre)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

.genre-card {
  a {
    background-color: color-mix(in srgb, var(--genre-color) 16%, transparent);

    &:hover {
      background-color: color-mix(in srgb, var(--genre-color) 28%, transparent);
    }
  }

  &[data-plain] a {
    background-color: color-mix(in srgb, var(--genre-color) 5%, transparent);

    &:hover {
      background-color: color-mix(in srgb, var(--genre-color) 10%, transparent);
    }
  }

  &[data-size='0'] a {
    @apply text-sm px-3 py-1.5 text-k-fg-70;
  }

  &[data-size='1'] a {
    @apply text-base px-3.5 py-2;
  }

  &[data-size='2'] a {
    @apply text-lg px-4 py-2;
  }

  &[data-size='3'] a {
    @apply text-xl px-4 py-2.5;
  }

  &[data-size='4'] a {
    @apply text-2xl px-5 py-3;
  }

  .count {
    font-size: 0.75em;
  }
}
</style>
