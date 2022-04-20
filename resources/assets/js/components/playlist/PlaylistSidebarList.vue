<template>
  <section id="playlists">
    <h1>Playlists
      <i
        :class="{ creating }"
        @click.prevent.stop="toggleContextMenu"
        class="fa fa-plus-circle control create"
        role="button"
        title="Create a new playlist"
        data-testid="sidebar-create-playlist-btn"
      ></i>
    </h1>

    <form v-if="creating" @submit.prevent="createPlaylist" name="create-simple-playlist-form" class="create">
      <input
        @keyup.esc.prevent="creating = false"
        placeholder="↵ to save"
        name="name"
        required
        type="text"
        v-koel-focus
        v-model="newName"
      >
    </form>

    <ul>
      <PlaylistItem type="favorites" :playlist="{ name: 'Favorites', songs: favoriteState.songs }"/>
      <PlaylistItem type="recently-played" :playlist="{ name: 'Recently Played', songs: [] }"/>
      <PlaylistItem
        :playlist="playlist"
        :key="playlist.id"
        type="playlist"
        v-for="playlist in playlists"
      />
    </ul>

    <ContextMenu ref="contextMenu" @createPlaylist="creating = true"/>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, nextTick, reactive, ref, toRef } from 'vue'
import { favoriteStore, playlistStore } from '@/stores'
import router from '@/router'

const PlaylistItem = defineAsyncComponent(() => import('@/components/playlist/PlaylistSidebarItem.vue'))
const ContextMenu = defineAsyncComponent(() => import('@/components/playlist/CreateNewPlaylistContextMenu.vue'))

const contextMenu = ref<InstanceType<typeof ContextMenu>>()

const playlists = toRef(playlistStore.state, 'playlists')
const favoriteState = reactive(favoriteStore.state)
const creating = ref(false)
const newName = ref('')

const createPlaylist = async () => {
  creating.value = false

  const playlist = await playlistStore.store(newName.value)
  newName.value = ''
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
  .control.create {
    margin: -8px -10px -10px;
    font-size: 16px;
    transition: .3s;
    padding: 10px;

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
