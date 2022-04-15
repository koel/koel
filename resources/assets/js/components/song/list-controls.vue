<template>
  <div class="song-list-controls" data-test="song-list-controls">
    <btn-group uppercased>
      <template v-if="mergedConfig.play">
        <template v-if="altPressed">
          <btn
            @click.prevent="playAll"
            class="btn-play-all"
            orange
            title="Play all songs"
            v-if="selectedSongs.length < 2 && songs.length"
            data-test="btn-play-all"
          >
            <i class="fa fa-play"></i> All
          </btn>

          <btn
            @click.prevent="playSelected"
            class="btn-play-selected"
            orange
            title="Play selected songs"
            v-if="selectedSongs.length > 1"
            data-test="btn-play-selected"
          >
            <i class="fa fa-play"></i> Selected
          </btn>
        </template>

        <template v-else>
          <btn
            @click.prevent="shuffle"
            class="btn-shuffle-all"
            orange
            title="Shuffle all songs"
            v-if="selectedSongs.length < 2 && songs.length"
            data-test="btn-shuffle-all"
          >
            <i class="fa fa-random"></i> All
          </btn>

          <btn
            @click.prevent="shuffleSelected"
            class="btn-shuffle-selected"
            orange
            title="Shuffle selected songs"
            v-if="selectedSongs.length > 1"
            data-test="btn-shuffle-selected"
          >
            <i class="fa fa-random"></i> Selected
          </btn>
        </template>
      </template>

      <btn
        :title="`${showingAddToMenu ? 'Cancel' : 'Add selected songs to…'}`"
        @click.prevent.stop="toggleAddToMenu"
        class="btn-add-to"
        green
        v-if="selectedSongs.length"
        data-test="add-to-btn"
      >
        {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
      </btn>

      <btn
        @click.prevent="clearQueue"
        class="btn-clear-queue"
        red
        v-if="showClearQueueButton"
        title="Clear current queue"
      >
        Clear
      </btn>

      <btn
        @click.prevent="deletePlaylist"
        class="del btn-delete-playlist"
        red
        title="Delete this playlist"
        v-if="showDeletePlaylistButton"
      >
        <i class="fa fa-times"></i> Playlist
      </btn>

    </btn-group>

    <add-to-menu
      @closing="closeAddToMenu"
      :config="mergedConfig.addTo"
      :songs="selectedSongs"
      :showing="showingAddToMenu"
      v-koel-clickaway="closeAddToMenu"
    />
  </div>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'

const KEYCODE_ALT = 18

export default Vue.extend({
  props: {
    config: {
      type: Object
    } as PropOptions<Partial<SongListControlsConfig>>,
    songs: {
      type: Array,
      default: () => []
    } as PropOptions<Song[]>,

    selectedSongs: {
      type: Array,
      default: () => []
    } as PropOptions<Song[]>
  },

  components: {
    AddToMenu: () => import('./add-to-menu.vue'),
    Btn: () => import('@/components/ui/btn.vue'),
    BtnGroup: () => import('@/components/ui/btn-group.vue')
  },

  data: () => ({
    showingAddToMenu: false,
    numberOfQueuedSongs: 0,
    altPressed: false
  }),

  computed: {
    showClearQueueButton (): boolean {
      return this.mergedConfig.clearQueue
    },

    showDeletePlaylistButton (): boolean {
      return this.mergedConfig.deletePlaylist
    },

    mergedConfig (): SongListControlsConfig {
      return Object.assign({
        play: true,
        addTo: {
          queue: true,
          favorites: true,
          playlists: true,
          newPlaylist: true
        },
        clearQueue: false,
        deletePlaylist: false
      }, this.config)
    }
  },

  methods: {
    shuffle (): void {
      this.$emit('playAll', true)
    },

    shuffleSelected (): void {
      this.$emit('playSelected', true)
    },

    playAll (): void {
      this.$emit('playAll', false)
    },

    playSelected (): void {
      this.$emit('playSelected', false)
    },

    clearQueue (): void {
      this.$emit('clearQueue')
    },

    deletePlaylist (): void {
      this.$emit('deletePlaylist')
    },

    closeAddToMenu (): void {
      this.showingAddToMenu = false
    },

    registerKeydown (event: KeyboardEvent): void {
      if (event.keyCode === KEYCODE_ALT) {
        this.altPressed = true
      }
    },

    registerKeyup (event: KeyboardEvent): void {
      if (event.keyCode === KEYCODE_ALT) {
        this.altPressed = false
      }
    },

    toggleAddToMenu () {
      this.showingAddToMenu = !this.showingAddToMenu

      if (!this.showingAddToMenu) {
        return
      }

      this.$nextTick(() => {
        const btnAddTo = this.$el.querySelector<HTMLButtonElement>('.btn-add-to')!
        const { left: btnLeft, bottom: btnBottom, width: btnWidth } = btnAddTo.getBoundingClientRect()
        const contextMenu = this.$el.querySelector<HTMLElement>('.add-to')!
        const menuWidth = contextMenu.getBoundingClientRect().width
        contextMenu.style.top = `${btnBottom + 10}px`
        contextMenu.style.left = `${btnLeft + btnWidth / 2 - menuWidth / 2}px`
      })
    }
  },

  mounted (): void {
    window.addEventListener('keydown', this.registerKeydown)
    window.addEventListener('keyup', this.registerKeyup)
  },

  destroyed (): void {
    window.removeEventListener('keydown', this.registerKeydown)
    window.removeEventListener('keyup', this.registerKeyup)
  }
})
</script>

<style lang="scss" scoped>
.song-list-controls {
  position: relative;
}
</style>
