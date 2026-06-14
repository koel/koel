<template>
  <SidebarSection>
    <SidebarSectionHeader class="flex items-center">
      <span class="flex-1">Playlists</span>
      <CreatePlaylistContextMenuButton />
    </SidebarSectionHeader>

    <ul
      :class="{ active: showDropAffordance, droppable }"
      class="rounded-md transition-colors"
      @dragleave="onDragLeave"
      @dragover="onDragOver"
      @drop="onDrop"
    >
      <PlaylistSidebarItem :list="{ name: 'Favorites', playables: favorites }" />
      <PlaylistSidebarItem :list="{ name: 'Recently Played', playables: [] }" />
      <PlaylistFolderSidebarItem v-for="folder in folders" :key="folder.id" :folder="folder" />
      <PlaylistSidebarItem v-for="playlist in orphanPlaylists" :key="playlist.id" :list="playlist" />
    </ul>
  </SidebarSection>
</template>

<script lang="ts" setup>
import { computed, ref, toRef } from 'vue'
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
const droppable = ref(false)

// Show the section's "drop here to leave a folder" hint only while a playlist
// is actively being dragged, so the rest of the time the sidebar is quiet.
const showDropAffordance = computed(() => currentDragType.value === 'playlist')

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
  droppable.value = true
}

const onDragLeave = (event: DragEvent) => {
  const relatedTarget = event.relatedTarget as Node | null
  if (relatedTarget && (event.currentTarget as Node).contains(relatedTarget)) {
    return
  }

  droppable.value = false
}

const onDrop = async (event: DragEvent) => {
  droppable.value = false

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
ul.active {
  @apply bg-k-fg-5;
}

ul.droppable {
  @apply ring-1 ring-offset-0 ring-k-highlight cursor-copy;
}
</style>
