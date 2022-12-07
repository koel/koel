<template>
  <SidebarItem
    screen="Queue"
    href="#/queue"
    :icon="faListOl"
    :class="droppable && 'droppable'"
    @dragleave="onQueueDragLeave"
    @dragover.prevent="onQueueDragOver"
    @drop="onQueueDrop"
  >
    Current Queue
  </SidebarItem>
</template>

<script lang="ts" setup>
import { faListOl } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { queueStore } from '@/stores'
import { useDroppable } from '@/composables'

import SidebarItem from './SidebarItem.vue'

const { acceptsDrop, resolveDroppedSongs } = useDroppable(['songs', 'album', 'artist', 'playlist'])

const droppable = ref(false)

const onQueueDragOver = (event: DragEvent) => (droppable.value = acceptsDrop(event))
const onQueueDragLeave = () => (droppable.value = false)

const onQueueDrop = async (event: DragEvent) => {
  droppable.value = false

  event.preventDefault()
  const songs = await resolveDroppedSongs(event) || []
  songs.length && queueStore.queue(songs)

  return false
}
</script>
