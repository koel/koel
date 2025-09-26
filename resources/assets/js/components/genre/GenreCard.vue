<template>
  <li
    :key="genre.name"
    class="rounded-lg overflow-hidden relative min-h-auto md:min-h-40 border border-px hover:scale-[1.02] transition-transform active:transition-none active:scale-100"
    draggable="true"
    tabindex="0"
    @dragstart="onDragStart"
    @contextmenu.prevent="onContextMenu"
  >
    <a
      :href="url('genres.show', { id: genre.id })"
      :title="genre.name || 'No Genre'"
      class="flex flex-col justify-end h-full p-4"
    >
      <span
        class="text-2xl overflow-hidden whitespace-nowrap text-ellipsis font-normal text-white/90"
        :class="genre.name || 'italic'"
      >
        {{ genre.name || 'No Genre' }}
      </span>
      <span class="text-white/70 text-lg">{{ pluralize(genre.song_count, 'song') }}</span>
    </a>
    <span
      class="absolute -z-10 pointer-events-none inset-0 bg-gradient-to-t from-black/70  via-black/20 to-transparent"
    />
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { pluralize, textToHsl } from '@/utils/formatters'
import { useRouter } from '@/composables/useRouter'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useContextMenu } from '@/composables/useContextMenu'
import { defineAsyncComponent } from '@/utils/helpers'

const props = defineProps<{ genre: Genre }>()

const ContextMenu = defineAsyncComponent(() => import('@/components/genre/GenreContextMenu.vue'))

const { url } = useRouter()
const { startDragging } = useDraggable('genre')
const { openContextMenu } = useContextMenu()

const color = computed(() => textToHsl(props.genre.id))

const onContextMenu = (event: MouseEvent) => openContextMenu<'GENRE'>(ContextMenu, event, {
  genre: props.genre,
})

const onDragStart = (event: DragEvent) => startDragging(event, props.genre)
</script>

<style scoped lang="postcss">
li {
  border-color: v-bind(color);
}
</style>
