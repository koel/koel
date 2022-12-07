<template>
  <section id="playlists">
    <h1>
      <span>Playlists</span>
      <icon
        :icon="faCirclePlus"
        class="control create"
        role="button"
        title="Create a new playlist or folder"
        @click.stop.prevent="requestContextMenu"
      />
    </h1>

    <ul>
      <PlaylistSidebarItem :list="{ name: 'Favorites', songs: favorites }" />
      <PlaylistSidebarItem :list="{ name: 'Recently Played', songs: [] }" />
      <PlaylistFolderSidebarItem v-for="folder in folders" :key="folder.id" :folder="folder" />
      <PlaylistSidebarItem v-for="playlist in orphanPlaylists" :key="playlist.id" :list="playlist" />
    </ul>
  </section>
</template>

<script lang="ts" setup>
import { faCirclePlus } from '@fortawesome/free-solid-svg-icons'
import { computed, toRef } from 'vue'
import { favoriteStore, playlistFolderStore, playlistStore } from '@/stores'
import { eventBus } from '@/utils'

import PlaylistSidebarItem from './PlaylistSidebarItem.vue'
import PlaylistFolderSidebarItem from './PlaylistFolderSidebarItem.vue'

const folders = toRef(playlistFolderStore.state, 'folders')
const playlists = toRef(playlistStore.state, 'playlists')
const favorites = toRef(favoriteStore.state, 'songs')

const orphanPlaylists = computed(() => playlists.value.filter(playlist => playlist.folder_id === null))

const requestContextMenu = (event: MouseEvent) => eventBus.emit('CREATE_NEW_PLAYLIST_CONTEXT_MENU_REQUESTED', event)
</script>

<style lang="scss">
#playlists {
  h1 {
    display: flex;
    align-items: center;

    span {
      flex: 1;
    }
  }

  .control.create {
    transition: .3s;

    &.creating {
      transform: rotate(135deg);
    }
  }
}
</style>
