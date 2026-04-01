<template>
  <li
    class="min-h-auto md:min-h-40"
    draggable="true"
    tabindex="0"
    @dragstart="onDragStart"
    @contextmenu.prevent="onContextMenu"
  >
    <WithGradientBorder
      :color
      border-color="color-mix(in srgb, var(--color-fg), transparent 97%)"
      border-width="1px"
      class="rounded-lg overflow-hidden relative h-full transition-transform active:transition-none active:scale-100"
    >
      <a
        :href="url('genres.show', { id: genre.id })"
        :title="genre.name || 'No Genre'"
        class="flex flex-col justify-end h-full p-4 bg-k-fg-3"
      >
        <span class="text-2xl truncate font-normal text-k-fg-90" :class="genre.name || 'italic'">
          {{ genre.name || 'No Genre' }}
        </span>
        <span class="text-k-fg-70 text-lg">{{ pluralize(genre.song_count, 'song') }}</span>
      </a>
    </WithGradientBorder>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { pluralize, textToHsl } from '@/utils/formatters'
import { useRouter } from '@/composables/useRouter'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useContextMenu } from '@/composables/useContextMenu'
import { defineAsyncComponent } from '@/utils/helpers'

import WithGradientBorder from '@/components/ui/WithGradientBorder.vue'

const props = defineProps<{ genre: Genre }>()

const ContextMenu = defineAsyncComponent(() => import('@/components/genre/GenreContextMenu.vue'))

const { url } = useRouter()
const { startDragging } = useDraggable('genre')
const { openContextMenu } = useContextMenu()

const color = computed(() => textToHsl(props.genre.id))

const onContextMenu = (event: MouseEvent) =>
  openContextMenu<'GENRE'>(ContextMenu, event, {
    genre: props.genre,
  })

const onDragStart = (event: DragEvent) => startDragging(event, props.genre)
</script>
