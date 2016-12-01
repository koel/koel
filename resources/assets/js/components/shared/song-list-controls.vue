<template>
  <div class="buttons song-list-controls">
    <button class="btn btn-orange btn-shuffle-all"
      @click.prevent="shuffle"
      v-if="fullConfig.shuffle && selectedSongs.length < 2">
      <i class="fa fa-random"></i> All
    </button>

    <button class="btn btn-orange btn-shuffle-selected"
      @click.prevent="shuffleSelected"
      v-if="fullConfig.shuffle && selectedSongs.length > 1">
      <i class="fa fa-random"></i> Selected
    </button>

    <button class="btn btn-green btn-add-to"
      @click.prevent.stop="showingAddToMenu = !showingAddToMenu"
      v-if="selectedSongs.length">
      {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
    </button>

    <button class="btn btn-red btn-clear-queue"
      @click.prevent="clearQueue"
      v-if="showClearQueueButton">
      Clear
    </button>

    <button class="del btn btn-red btn-delete-playlist" v-if="showDeletePlaylistButton"
      title="Delete this playlist"
      @click.prevent="deletePlaylist">
      <i class="fa fa-times"></i> Playlist
    </button>

    <add-to-menu v-koel-clickaway="closeAddToMenu"
      :config="fullConfig.addTo"
      :songs="selectedSongs"
      :showing="showingAddToMenu"
    />
  </div>
</template>

<script>
import { assign } from 'lodash'
import addToMenu from './add-to-menu.vue'

export default {
  name: 'shared--song-list-controls',
  props: ['config', 'selectedSongs'],

  components: { addToMenu },

  data () {
    return {
      fullConfig: {
        shuffle: true,
        addTo: {
          queue: true,
          favorites: true,
          playlists: true,
          newPlaylist: true
        },
        clearQueue: false,
        deletePlaylist: false
      },
      showingAddToMenu: false,
      numberOfQueuedSongs: 0
    }
  },

  computed: {
    showClearQueueButton () {
      return this.fullConfig.clearQueue
    },

    showDeletePlaylistButton () {
      return this.fullConfig.deletePlaylist
    }
  },

  mounted () {
    assign(this.fullConfig, this.config)
  },

  methods: {
    shuffle () {
      this.$emit('shuffleAll')
    },

    shuffleSelected () {
      this.$emit('shuffleSelected')
    },

    clearQueue () {
      this.$emit('clearQueue')
    },

    deletePlaylist () {
      this.$emit('deletePlaylist')
    },

    closeAddToMenu () {
      this.showingAddToMenu = false
    }
  }
}
</script>

<style lang="sass"></style>
