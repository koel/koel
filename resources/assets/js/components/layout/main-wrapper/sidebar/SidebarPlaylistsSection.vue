<template>
  <SidebarSection>
    <SidebarSectionHeader class="flex items-center">
      <span class="flex-1">Playlists</span>
      <CreatePlaylistContextMenuButton />
    </SidebarSectionHeader>

    <ul v-koel-overflow-fade class="max-h-[300px] overflow-y-auto">
      <PlaylistSidebarItem :list="{ name: 'Favorites', songs: favorites }" />
      <PlaylistSidebarItem :list="{ name: 'Recently Played', songs: [] }" />
      <PlaylistFolderSidebarItem v-for="folder in folders" :key="folder.id" :folder="folder" />
      <PlaylistSidebarItem v-for="playlist in orphanPlaylists" :key="playlist.id" :list="playlist" />
    </ul>
  </SidebarSection>
</template>

<script lang="ts" setup>
import { computed, toRef } from 'vue'
import { favoriteStore, playlistFolderStore, playlistStore } from '@/stores'

import PlaylistSidebarItem from './PlaylistSidebarItem.vue'
import PlaylistFolderSidebarItem from './PlaylistFolderSidebarItem.vue'
import CreatePlaylistContextMenuButton from '@/components/playlist/CreatePlaylistContextMenuButton.vue'
import SidebarSectionHeader from '@/components/layout/main-wrapper/sidebar/SidebarSectionHeader.vue'
import SidebarSection from '@/components/layout/main-wrapper/sidebar/SidebarSection.vue'

const folders = toRef(playlistFolderStore.state, 'folders')
const playlists = toRef(playlistStore.state, 'playlists')
const favorites = toRef(favoriteStore.state, 'songs')

const orphanPlaylists = computed(() => playlists.value.filter(({ folder_id }) => {
  if  (folder_id === null) return true

  // if the playlist's folder is not found, it's an orphan
  // this can happen if the playlist belongs to another user (collaborative playlist)
  return !folders.value.find(folder => folder.id === folder_id)
}))
</script>
