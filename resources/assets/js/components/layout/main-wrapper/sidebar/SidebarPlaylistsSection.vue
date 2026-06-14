<template>
  <SidebarSection>
    <SidebarSectionHeader class="flex items-center">
      <span class="flex-1">Playlists</span>
      <CreatePlaylistContextMenuButton />
    </SidebarSectionHeader>

    <ul>
      <PlaylistSidebarItem :list="{ name: 'Favorites', playables: favorites }" />
      <PlaylistSidebarItem :list="{ name: 'Recently Played', playables: [] }" />
      <PlaylistFolderSidebarItem v-for="folder in folders" :key="folder.id" :folder="folder" />
      <PlaylistSidebarItem v-for="playlist in orphanPlaylists" :key="playlist.id" :list="playlist" />

      <li
        v-if="showOutOfFolderZone"
        :class="{ droppable }"
        class="drop-zone mt-2 px-3 py-2 rounded-md text-sm text-k-text-secondary border border-dashed border-k-fg-30 text-center select-none"
        @dragleave="onZoneDragLeave"
        @dragover="onZoneDragOver"
        @drop="onZoneDrop"
      >
        Drop here to move out of folder
      </li>
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

// Only show the "move out of folder" zone while a playlist is actively being dragged.
const showOutOfFolderZone = computed(() => currentDragType.value === 'playlist')

const onZoneDragOver = (event: DragEvent) => {
  if (!acceptsDrop(event)) {
    return false
  }

  event.preventDefault()
  event.stopPropagation()
  droppable.value = true
}

const onZoneDragLeave = (event: DragEvent) => {
  const relatedTarget = event.relatedTarget as Node | null
  if (relatedTarget && (event.currentTarget as Node).contains(relatedTarget)) {
    return
  }

  droppable.value = false
}

const onZoneDrop = async (event: DragEvent) => {
  droppable.value = false

  if (!acceptsDrop(event)) {
    return false
  }

  event.preventDefault()
  event.stopPropagation()

  const playlist = await resolveDroppedValue<Playlist>(event)
  if (!playlist || playlist.folder_id === null) {
    return
  }

  await playlistFolderStore.movePlaylistToFolder(playlist, null)
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.drop-zone.droppable {
  @apply border-solid border-k-highlight text-k-highlight cursor-copy;
}
</style>
