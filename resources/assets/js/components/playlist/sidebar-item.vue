<template>
  <li
    @dblclick.prevent="makeEditable"
    :class="['playlist', type, editing ? 'editing' : '', playlist.is_smart ? 'smart' : '']">
    <a
      :class="{ active }"
      :href="url"
      @contextmenu.prevent="openContextMenu"
      v-koel-droppable="handleDrop"
    >{{ playlist.name }}</a>

    <name-editor
      :playlist="playlist"
      @cancelled="cancelEditing"
      @updated="onPlaylistNameUpdated"
      v-if="nameEditable && editing"
    />

    <context-menu
      v-if="hasContextMenu"
      v-show="showingContextMenu"
      :playlist="playlist"
      ref="contextMenu"
      @edit="makeEditable"
    />
  </li>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { BaseContextMenu } from 'koel/types/ui'
import { eventBus } from '@/utils'
import router from '@/router'
import { songStore, playlistStore, favoriteStore } from '@/stores'

const VALID_PLAYLIST_TYPES = ['playlist', 'favorites', 'recently-played']

export default Vue.extend({
  components: {
    ContextMenu: () => import('@/components/playlist/item-context-menu.vue'),
    NameEditor: () => import('@/components/playlist/name-editor.vue')
  },

  props: {
    playlist: {
      type: Object,
      required: true
    } as PropOptions<Playlist>,

    type: {
      type: String,
      default: 'playlist',
      validator: value => VALID_PLAYLIST_TYPES.includes(value)
    }
  },

  data: () => ({
    editing: false,
    active: false,
    showingContextMenu: false
  }),

  computed: {
    url (): string {
      switch (this.type) {
        case 'playlist':
          return `#!/playlist/${this.playlist.id}`

        case 'favorites':
          return '#!/favorites'

        case 'recently-played':
          return '#!/recently-played'

        default:
          throw new Error('Invalid playlist type')
      }
    },

    nameEditable (): boolean {
      return this.type === 'playlist'
    },

    contentEditable (): boolean {
      if (this.playlist.is_smart) {
        return false
      }

      return this.type === 'playlist' || this.type === 'favorites'
    },

    hasContextMenu (): boolean {
      return this.type === 'playlist'
    }
  },

  methods: {
    makeEditable (): void {
      if (!this.nameEditable) {
        return
      }

      this.editing = true
    },

    /**
     * Handle songs dropped to our favorite or playlist menu item.
     */
    handleDrop (e: DragEvent): boolean {
      if (!this.contentEditable) {
        return false
      }

      if (!e.dataTransfer?.getData('application/x-koel.text+plain')) {
        return false
      }

      const songs = songStore.byIds(e.dataTransfer.getData('application/x-koel.text+plain').split(','))

      if (!songs.length) {
        return false
      }

      if (this.type === 'favorites') {
        favoriteStore.like(songs)
      } else if (this.type === 'playlist') {
        playlistStore.addSongs(this.playlist, songs)
      }

      return false
    },

    async openContextMenu (event: MouseEvent) {
      if (this.hasContextMenu) {
        this.showingContextMenu = true
        await this.$nextTick()
        router.go(`/playlist/${this.playlist.id}`)
        ;(this.$refs.contextMenu as BaseContextMenu).open(event.pageY, event.pageX)
      }
    },

    cancelEditing (): void {
      this.editing = false
    },

    onPlaylistNameUpdated (mutatedPlaylist: Playlist): void {
      this.playlist.name = mutatedPlaylist.name
      this.editing = false
    }
  },

  created (): void {
    eventBus.on('LOAD_MAIN_CONTENT', (view: MainViewName, playlist: Playlist): void => {
      switch (view) {
        case 'Favorites':
          this.active = this.type === 'favorites'
          break

        case 'RecentlyPlayed':
          this.active = this.type === 'recently-played'

          break
        case 'Playlist':
          this.active = this.playlist === playlist
          break

        default:
          this.active = false
          break
      }
    })
  }
})
</script>

<style lang="scss" scoped>
.playlist {
  user-select: none;

  a {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;

    span {
      pointer-events: none;
    }

    &::before {
      content: "\f0f6";
    }
  }

  &.favorites a::before {
    content: "\f004";
    color: var(--color-maroon);
  }

  &.recently-played a::before {
    content: "\f1da";
    color: var(--color-green);
  }

  &.smart a::before {
    content: "\f069";
  }

  input {
    width: calc(100% - 32px);
    margin: 5px 16px;
  }

  &.editing {
    a {
      display: none !important;
    }
  }
}
</style>
