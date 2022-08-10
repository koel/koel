<template>
  <section id="playlists">
    <h1>
      <span>Playlists</span>
      <icon
        :icon="faCirclePlus"
        class="control create"
        data-testid="sidebar-create-playlist-btn"
        role="button"
        title="Create a new playlist or folder"
        @click.stop.prevent="toggleContextMenu"
      />
    </h1>

    <ul>
      <PlaylistSidebarItem :playlist="{ name: 'Favorites', songs: favorites }" type="favorites"/>
      <PlaylistSidebarItem :playlist="{ name: 'Recently Played', songs: [] }" type="recently-played"/>
      <PlaylistFolderSidebarItem v-for="folder in folders" :key="folder.id" :folder="folder"/>
      <PlaylistSidebarItem
        v-for="playlist in rootLevelPlaylists"
        :key="playlist.id"
        :playlist="playlist"
        type="playlist"
      />
    </ul>

    <ContextMenu ref="contextMenu"/>
  </section>
</template>

<script lang="ts" setup>
import { faCirclePlus } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, ref, toRef } from 'vue'
import { favoriteStore, playlistFolderStore, playlistStore } from '@/stores'
import { requireInjection } from '@/utils'
import { MessageToasterKey } from '@/symbols'

import ContextMenu from '@/components/playlist/CreateNewPlaylistContextMenu.vue'
import PlaylistSidebarItem from '@/components/playlist/PlaylistSidebarItem.vue'
import PlaylistFolderSidebarItem from '@/components/playlist/PlaylistFolderSidebarItem.vue'

const toaster = requireInjection(MessageToasterKey)
const contextMenu = ref<InstanceType<typeof ContextMenu>>()

const folders = toRef(playlistFolderStore.state, 'folders')
const playlists = toRef(playlistStore.state, 'playlists')
const favorites = toRef(favoriteStore.state, 'songs')

const rootLevelPlaylists: Playlist[] = computed(() => playlists.value.filter(playlist => playlist.folder_id === null))

const toggleContextMenu = async (event: MouseEvent) => {
  await nextTick()
  contextMenu.value?.open(event.pageY, event.pageX)
}
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
