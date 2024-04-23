<template>
  <SidebarItem
    :class="droppable && 'droppable'"
    href="#/queue"
    screen="Queue"
    @dragleave="onQueueDragLeave"
    @drop="onQueueDrop"
    @dragover.prevent="onQueueDragOver"
  >
    <template #icon>
      <Icon :icon="faListOl" fixed-width />
    </template>
    Current Queue
  </SidebarItem>
</template>

<script lang="ts" setup>
import { faListOl } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { queueStore } from '@/stores'
import { useDroppable, useMessageToaster } from '@/composables'

import SidebarItem from './SidebarItem.vue'
import { pluralize } from '@/utils'

const { toastWarning, toastSuccess } = useMessageToaster()
const { acceptsDrop, resolveDroppedSongs } = useDroppable(['songs', 'album', 'artist', 'playlist', 'playlist-folder'])

const droppable = ref(false)

const onQueueDragOver = (event: DragEvent) => (droppable.value = acceptsDrop(event))
const onQueueDragLeave = () => (droppable.value = false)

const onQueueDrop = async (event: DragEvent) => {
  droppable.value = false

  event.preventDefault()
  const songs = await resolveDroppedSongs(event) || []

  if (songs.length) {
    queueStore.queue(songs)
    toastSuccess(`Added ${pluralize(songs, 'song')} to queue.`)
  } else {
    toastWarning('No applicable songs to queue.')
  }

  return false
}
</script>
