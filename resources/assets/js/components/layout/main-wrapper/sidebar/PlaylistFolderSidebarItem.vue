<template>
  <li
    :class="{ droppable }"
    class="playlist-folder relative"
    draggable="true"
    tabindex="0"
    @dragleave="onDragLeave"
    @dragover="onDragOver"
    @dragstart="onDragStart"
    @drop="onDrop"
  >
    <ul>
      <SidebarItem @click="toggle" @contextmenu.prevent="onContextMenu">
        <template #icon>
          <Icon :icon="opened ? faFolderOpen : faFolder" fixed-width />
        </template>
        {{ folder.name }}
      </SidebarItem>

      <li v-if="playlistsInFolder.length" v-show="opened">
        <ul>
          <PlaylistSidebarItem
            v-for="playlist in playlistsInFolder"
            :key="playlist.id"
            :list="playlist"
            class="pl-4"
          />
        </ul>
      </li>

      <li
        v-if="opened"
        :class="droppableOnHatch && 'droppable'"
        class="hatch absolute bottom-0 w-full h-1"
        @dragover="onDragOverHatch"
        @dragleave.prevent="onDragLeaveHatch"
        @drop.prevent="onDropOnHatch"
      />
    </ul>
  </li>
</template>

<script lang="ts" setup>
import { faFolder, faFolderOpen } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRefs } from 'vue'
import { playlistFolderStore, playlistStore } from '@/stores'
import { eventBus } from '@/utils'
import { useDraggable, useDroppable } from '@/composables'

import PlaylistSidebarItem from './PlaylistSidebarItem.vue'
import SidebarItem from './SidebarItem.vue'

const props = defineProps<{ folder: PlaylistFolder }>()
const { folder } = toRefs(props)

const opened = ref(false)
const droppable = ref(false)
const droppableOnHatch = ref(false)

const playlistsInFolder = computed(() => playlistStore.byFolder(folder.value))

const { acceptsDrop, resolveDroppedValue } = useDroppable(['playlist'])
const { startDragging } = useDraggable('playlist-folder')

const toggle = () => (opened.value = !opened.value)

const onDragStart = (event: DragEvent) => startDragging(event, folder.value)

const onDragOver = (event: DragEvent) => {
  if (!acceptsDrop(event)) return false

  event.preventDefault()
  droppable.value = true
  opened.value = true
}

const onDragLeave = () => (droppable.value = false)

const onDrop = async (event: DragEvent) => {
  droppable.value = false

  if (!acceptsDrop(event)) return false

  event.preventDefault()

  const playlist = await resolveDroppedValue<Playlist>(event)
  if (!playlist || playlist.folder_id === folder.value.id) return

  await playlistFolderStore.addPlaylistToFolder(folder.value, playlist)
}

const onDragLeaveHatch = () => (droppableOnHatch.value = false)

const onDragOverHatch = (event: DragEvent) => {
  if (!acceptsDrop(event)) return false

  event.preventDefault()
  droppableOnHatch.value = true
}

const onDropOnHatch = async (event: DragEvent) => {
  droppableOnHatch.value = false
  droppable.value = false

  const playlist = (await resolveDroppedValue<Playlist>(event))!

  // if the playlist isn't in the folder, don't do anything. The folder will handle the drop.
  if (playlist.folder_id !== folder.value.id) return

  // otherwise, the user is trying to remove the playlist from the folder.
  event.stopPropagation()
  await playlistFolderStore.removePlaylistFromFolder(folder.value, playlist)
}

const onContextMenu = (event: MouseEvent) => eventBus.emit(
  'PLAYLIST_FOLDER_CONTEXT_MENU_REQUESTED',
  event,
  folder.value
)
</script>

<style lang="postcss" scoped>
.droppable {
  @apply ring-1 ring-offset-0 ring-k-accent rounded-md cursor-copy;
}

.hatch.droppable {
  @apply border-b-[3px] border-k-highlight;
}
</style>
