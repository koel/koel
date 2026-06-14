<template>
  <SidebarSection>
    <SidebarSectionHeader class="flex items-center">
      <span class="flex-1">Playlists</span>
      <CreatePlaylistContextMenuButton />
    </SidebarSectionHeader>

    <ul :class="{ dragging: isDraggingPlaylist, 'has-target': hasDropTarget }" @dragover="onDragOver" @drop="onDrop">
      <PlaylistSidebarItem :list="{ name: 'Favorites', playables: favorites }" />
      <PlaylistSidebarItem :list="{ name: 'Recently Played', playables: [] }" />
      <PlaylistFolderSidebarItem v-for="folder in folders" :key="folder.id" :folder="folder" />
      <PlaylistSidebarItem v-for="playlist in orphanPlaylists" :key="playlist.id" :list="playlist" />
    </ul>
  </SidebarSection>
</template>

<script lang="ts" setup>
import { computed, toRef } from 'vue'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import { playableStore } from '@/stores/playableStore'
import { currentDragType, currentDropTargetFolderId, useDroppable } from '@/composables/useDragAndDrop'

import PlaylistSidebarItem from './PlaylistSidebarItem.vue'
import PlaylistFolderSidebarItem from './PlaylistFolderSidebarItem.vue'
import CreatePlaylistContextMenuButton from '@/components/playlist/CreatePlaylistContextMenuButton.vue'
import SidebarSectionHeader from '@/components/layout/main-wrapper/sidebar/SidebarSectionHeader.vue'
import SidebarSection from '@/components/layout/main-wrapper/sidebar/SidebarSection.vue'

const folders = toRef(playlistFolderStore.state, 'folders')
const playlists = toRef(playlistStore.state, 'playlists')
const favorites = toRef(playableStore.state, 'favorites')

const { acceptsDrop, resolveDroppedValue } = useDroppable(['playlist'])

const isDraggingPlaylist = computed(() => currentDragType.value === 'playlist')
const hasDropTarget = computed(() => currentDropTargetFolderId.value !== null)

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

// The section is the implicit "no folder" target: dropping anywhere that isn't
// a folder (folders stop propagation on accept) means "move out of any folder".
const onDragOver = (event: DragEvent) => {
  if (!acceptsDrop(event)) {
    return false
  }

  event.preventDefault()
}

const onDrop = async (event: DragEvent) => {
  if (!acceptsDrop(event)) {
    return false
  }

  event.preventDefault()

  const playlist = await resolveDroppedValue<Playlist>(event)
  if (!playlist || playlist.folder_id === null) {
    return
  }

  await playlistFolderStore.movePlaylistToFolder(playlist, null)
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

/* Two states while a playlist is being dragged:

   1. No drop target under the cursor — the source folder dims, signalling
      "you're leaving me." Everything else stays at full opacity, so the
      whole non-source area reads as a valid "drop to remove from folder."

   2. Cursor over an accepting folder — invert: the target folder pops at
      full opacity, everything else (including the source) dims, signalling
      "drop here to add to this folder." */
ul.dragging > :deep(*) {
  transition: opacity 0.15s ease;
}

ul.dragging > :deep(.drag-source) {
  opacity: 0.4;
}

ul.dragging.has-target > :deep(*) {
  opacity: 0.4;
}

ul.dragging.has-target > :deep(.drag-target) {
  opacity: 1;
}
</style>
