<template>
  <SidebarSection>
    <SidebarSectionHeader class="flex items-center">
      <span class="flex-1">Playlists</span>
      <CreatePlaylistContextMenuButton />
    </SidebarSectionHeader>

    <ul
      :class="{ dragging: isDraggingItem, 'has-folder-target': isDraggingItem && hasFolderTarget }"
      @dragover="onDragOver"
      @drop="onDrop"
    >
      <PlaylistSidebarItem :list="{ name: 'Favorites', playables: favorites }" />
      <PlaylistSidebarItem :list="{ name: 'Recently Played', playables: [] }" />
      <PlaylistFolderSidebarItem v-for="folder in rootFolders" :key="folder.id" :folder />
      <PlaylistSidebarItem v-for="playlist in orphanPlaylists" :key="playlist.id" :list="playlist" />
    </ul>
  </SidebarSection>
</template>

<script lang="ts" setup>
import { computed, onBeforeUnmount, onMounted, provide, ref, toRef } from 'vue'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import { playableStore } from '@/stores/playableStore'
import { currentDragType, setDragText, useDroppable } from '@/composables/useDragAndDrop'
import { DraggedPlaylistFolderKey, DraggedPlaylistKey, PlaylistFolderDropTargetKey } from '@/config/symbols'

import PlaylistSidebarItem from './PlaylistSidebarItem.vue'
import PlaylistFolderSidebarItem from './PlaylistFolderSidebarItem.vue'
import CreatePlaylistContextMenuButton from '@/components/playlist/CreatePlaylistContextMenuButton.vue'
import SidebarSectionHeader from '@/components/layout/main-wrapper/sidebar/SidebarSectionHeader.vue'
import SidebarSection from '@/components/layout/main-wrapper/sidebar/SidebarSection.vue'

const folders = toRef(playlistFolderStore.state, 'folders')
const playlists = toRef(playlistStore.state, 'playlists')
const favorites = toRef(playableStore.state, 'favorites')

const rootFolders = computed(() => playlistFolderStore.byParent(null))

const { acceptsDrop, resolveDroppedValue } = useDroppable(['playlist', 'playlist-folder'])

const isDraggingItem = computed(
  () => currentDragType.value === 'playlist' || currentDragType.value === 'playlist-folder',
)

const folderDropTargetId = ref<string | null>(null)
provide(PlaylistFolderDropTargetKey, folderDropTargetId)
const hasFolderTarget = computed(() => folderDropTargetId.value !== null)

const draggedPlaylist = ref<Playlist | null>(null)
provide(DraggedPlaylistKey, draggedPlaylist)
const draggedPlaylistFolder = ref<PlaylistFolder | null>(null)
provide(DraggedPlaylistFolderKey, draggedPlaylistFolder)

const clearDraggedItems = () => {
  draggedPlaylist.value = null
  draggedPlaylistFolder.value = null
}
onMounted(() => document.addEventListener('dragend', clearDraggedItems))
onBeforeUnmount(() => document.removeEventListener('dragend', clearDraggedItems))

const orphanPlaylists = computed(() =>
  playlists.value.filter(({ folder_id }) => {
    if (folder_id === null) {
      return true
    }

    // if the playlist's folder is not found, it's an orphan
    // this can happen if the playlist belongs to another user (collaborative playlist)
    return !folders.value.find(folder => folder.id === folder_id)
  }),
)

const onDragOver = (event: DragEvent) => {
  if (!acceptsDrop(event)) {
    return false
  }

  event.preventDefault()

  const isFolderDrag = currentDragType.value === 'playlist-folder'

  // macOS ignores CSS cursor: during DnD; dropEffect drives the native + cursor.
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = isFolderDrag ? 'move' : 'copy'
  }

  if (isFolderDrag) {
    const folder = draggedPlaylistFolder.value
    setDragText(folder?.parent_id ? `Move ${folder.name} to root` : '')
    return
  }

  const playlist = draggedPlaylist.value
  if (!playlist?.folder_id) {
    setDragText('')
    return
  }

  const sourceFolder = playlistFolderStore.byId(playlist.folder_id)
  setDragText(sourceFolder ? `Move ${playlist.name} out of ${sourceFolder.name}` : '')
}

const onDrop = async (event: DragEvent) => {
  if (!acceptsDrop(event)) {
    return false
  }

  event.preventDefault()

  const dropped = await resolveDroppedValue<Playlist | PlaylistFolder>(event)
  if (!dropped) {
    return
  }

  if (dropped.type === 'playlist-folders') {
    await playlistFolderStore.moveFolderToFolder(dropped, null)
    return
  }

  if (dropped.folder_id !== null) {
    await playlistFolderStore.movePlaylistToFolder(dropped, null)
  }
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

ul.dragging {
  cursor: copy;
}

ul.dragging:not(.has-folder-target) {
  @apply outline-1 outline-dashed outline-offset-2 outline-k-highlight rounded-md;
}

ul.dragging.has-folder-target > :deep(*) {
  opacity: 0.4;
  transition: opacity 0.15s ease;
}

ul.dragging.has-folder-target > :deep(.drop-target-path) {
  opacity: 1;
}
</style>
