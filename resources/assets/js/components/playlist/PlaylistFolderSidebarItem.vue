<template>
  <li
    class="playlist-folder"
    :class="{ droppable }"
    @dragleave="onDragLeave"
    @dragover="onDragOver"
    @drop="onDrop"
    tabindex="0"
  >
    <a @click.prevent="toggle" @contextmenu.prevent="onContextMenu">
      <icon :icon="opened ? faFolderOpen : faFolder" fixed-width/>
      {{ folder.name }}
    </a>

    <ul v-if="playlistsInFolder.length" v-show="opened">
      <PlaylistSidebarItem v-for="playlist in playlistsInFolder" :key="playlist.id" :list="playlist" class="sub-item"/>
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
import { useDroppable } from '@/composables'

const PlaylistSidebarItem = defineAsyncComponent(() => import('@/components/playlist/PlaylistSidebarItem.vue'))

const props = defineProps<{ folder: PlaylistFolder }>()
const { folder } = toRefs(props)

const opened = ref(false)
const droppable = ref(false)
const droppableOnHatch = ref(false)

const playlistsInFolder = computed(() => playlistStore.byFolder(folder.value))

const { acceptsDrop, resolveDroppedValue } = useDroppable(['playlist'])

const toggle = () => (opened.value = !opened.value)

const onDragOver = (event: DragEvent) => {
  if (!acceptsDrop(event)) return false

  event.preventDefault()
  event.dataTransfer!.dropEffect = 'move'
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
  event.dataTransfer!.dropEffect = 'move'
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

const onContextMenu = event => eventBus.emit('PLAYLIST_FOLDER_CONTEXT_MENU_REQUESTED', event, folder.value)
</script>

<style lang="scss" scoped>
li.playlist-folder {
  position: relative;

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
