<template>
  <li
    class="playlist-folder"
    :class="{ droppable }"
    tabindex="0"
    draggable="true"
    @dragleave="onDragLeave"
    @dragover="onDragOver"
    @dragstart="onDragStart"
    @drop="onDrop"
  >
    <a @click.prevent="toggle" @contextmenu.prevent="onContextMenu">
      <icon :icon="opened ? faFolderOpen : faFolder" fixed-width />
      <span>{{ folder.name }}</span>
    </a>

    <ul v-if="playlistsInFolder.length" v-show="opened">
      <PlaylistSidebarItem v-for="playlist in playlistsInFolder" :key="playlist.id" :list="playlist" class="sub-item" />
    </ul>

    <div
      v-if="opened"
      :class="droppableOnHatch && 'droppable'"
      class="hatch"
      @dragover="onDragOverHatch"
      @dragleave.prevent="onDragLeaveHatch"
      @drop.prevent="onDropOnHatch"
    />
  </li>
</template>

<script lang="ts" setup>
import { faFolder, faFolderOpen } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, ref, toRefs } from 'vue'
import { playlistFolderStore, playlistStore } from '@/stores'
import { eventBus } from '@/utils'
import { useDraggable, useDroppable } from '@/composables'

const PlaylistSidebarItem = defineAsyncComponent(() => import('./PlaylistSidebarItem.vue'))

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

<style lang="scss" scoped>
li.playlist-folder {
  position: relative;

  a {
    color: var(--color-text-secondary);
  }

  &.droppable {
    box-shadow: inset 0 0 0 1px var(--color-accent);
    border-radius: 4px;
    cursor: copy;
  }

  .hatch {
    position: absolute;
    bottom: 0;
    width: 100%;
    height: .5rem;

    &.droppable {
      border-bottom: 3px solid var(--color-highlight);
    }
  }
}
</style>
