<template>
  <section id="playlists">
    <h1>Playlists
      <i
        :class="{ creating }"
        @click="toggleContextMenu"
        class="fa fa-plus-circle create"
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
      <playlist-item type="favorites" :playlist="{ name: 'Favorites', songs: favoriteState.songs }"/>
      <playlist-item type="recently-played" :playlist="{ name: 'Recently Played', songs: [] }"/>
      <playlist-item
        :playlist="playlist"
        :key="playlist.id"
        type="playlist"
        v-for="playlist in playlistState.playlists"
      />
    </ul>

    <context-menu ref="contextMenu" @createPlaylist="creating = true"/>
  </section>
</template>

<script lang="ts">
import Vue from 'vue'
import { BaseContextMenu } from 'koel/types/ui'
import { playlistStore, favoriteStore, recentlyPlayedStore } from '@/stores'
import router from '@/router'

export default Vue.extend({
  components: {
    PlaylistItem: () => import('@/components/playlist/sidebar-item.vue'),
    ContextMenu: () => import('@/components/playlist/create-new-context-menu.vue')
  },

  data: () => ({
    playlistState: playlistStore.state,
    favoriteState: favoriteStore.state,
    recentlyPlayedState: recentlyPlayedStore.state,
    creating: false,
    newName: ''
  }),

  methods: {
    async createPlaylist (): Promise<void> {
      this.creating = false

      const playlist = await playlistStore.store(this.newName)
      this.newName = ''
      // Activate the new playlist right away
      this.$nextTick(() => router.go(`playlist/${playlist.id}`))
    },

    toggleContextMenu (event: MouseEvent): void {
      this.$nextTick((): void => {
        if (this.creating) {
          this.creating = false
        } else {
          (this.$refs.contextMenu as BaseContextMenu).open(event.pageY, event.pageX)
        }
      })
    }
  }
})
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
