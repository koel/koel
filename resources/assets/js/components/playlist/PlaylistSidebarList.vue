<template>
  <section id="playlists">
    <h1>
      <span>Playlists</span>
      <icon
        :class="{ creating }"
        :icon="faCirclePlus"
        class="control create"
        data-testid="sidebar-create-playlist-btn"
        role="button"
        title="Create a new playlist"
        @click.stop.prevent="toggleContextMenu"
      />
    </h1>

    <form v-if="creating" @submit.prevent="createPlaylist" name="create-simple-playlist-form" class="create">
      <input
        v-model="newName"
        v-koel-focus
        name="name"
        placeholder="↵ to save"
        required
        type="text"
        @keyup.esc.prevent="creating = false"
      >
    </form>

    <ul>
      <PlaylistSidebarItem :playlist="{ name: 'Favorites', songs: favorites }" type="favorites"/>
      <PlaylistSidebarItem :playlist="{ name: 'Recently Played', songs: [] }" type="recently-played"/>
      <PlaylistSidebarItem
        v-for="playlist in playlists"
        :key="playlist.id"
        :playlist="playlist"
        type="playlist"
      />
    </ul>

    <ContextMenu ref="contextMenu" @createPlaylist="creating = true"/>
  </section>
</template>

<script lang="ts" setup>
import { faCirclePlus } from '@fortawesome/free-solid-svg-icons'
import { nextTick, ref, toRef } from 'vue'
import { favoriteStore, playlistStore } from '@/stores'
import router from '@/router'
import { requireInjection } from '@/utils'
import { MessageToasterKey } from '@/symbols'

import PlaylistSidebarItem from '@/components/playlist/PlaylistSidebarItem.vue'
import ContextMenu from '@/components/playlist/CreateNewPlaylistContextMenu.vue'

const toaster = requireInjection(MessageToasterKey)
const contextMenu = ref<InstanceType<typeof ContextMenu>>()

const playlists = toRef(playlistStore.state, 'playlists')
const favorites = toRef(favoriteStore.state, 'songs')
const creating = ref(false)
const newName = ref('')

const createPlaylist = async () => {
  creating.value = false

  const playlist = await playlistStore.store(newName.value)
  newName.value = ''

  toaster.value.success(`Playlist "${playlist.name}" created.`)

  // Activate the new playlist right away
  await nextTick()
  router.go(`playlist/${playlist.id}`)
}

const toggleContextMenu = async (event: MouseEvent) => {
  await nextTick()
  if (creating.value) {
    creating.value = false
  } else {
    contextMenu.value?.open(event.pageY, event.pageX)
  }
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

  form.create {
    padding: 8px 16px;

    input[type="text"] {
      width: 100%;
    }
  }
}
</style>
