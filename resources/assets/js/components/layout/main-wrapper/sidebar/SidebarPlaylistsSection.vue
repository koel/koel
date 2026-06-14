<template>
  <SidebarSection>
    <SidebarSectionHeader class="flex items-center">
      <span class="flex-1">Playlists</span>
      <CreatePlaylistContextMenuButton />
    </SidebarSectionHeader>

    <ul :class="{ dragging: isDraggingPlaylist }" @dragover="onDragOver" @drop="onDrop">
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
import { currentDragType, useDroppable } from '@/composables/useDragAndDrop'

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

  // The browser's drag-and-drop cursor on macOS ignores CSS `cursor:`; setting
  // dropEffect explicitly is how we get the copy (+) cursor while hovering.
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'copy'
  }
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

   1. No folder under the cursor — the section as a whole is the implicit
      "move out of folder" target. Surface a dashed outline around the whole
      <ul> and keep every entry at full opacity; dimming would lie about the
      drop being unavailable.
   2. Cursor over a folder — that folder is the specific target. Dim every
      entry except the folder under the cursor, which keeps full opacity to
      identify it as the accepting target. */
ul.dragging {
  cursor: copy;
}

ul.dragging:not(:has(:deep(.droppable))) {
  @apply outline-1 outline-dashed outline-offset-2 outline-k-highlight rounded-md;
}

ul.dragging:has(:deep(.droppable)) > :deep(*) {
  opacity: 0.4;
  transition: opacity 0.15s ease;
}

ul.dragging:has(:deep(.droppable)) > :deep(.droppable) {
  opacity: 1;
}
</style>
