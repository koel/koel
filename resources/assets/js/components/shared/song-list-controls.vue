<template>
  <div class="buttons song-list-controls">
    <button class="play-shuffle btn btn-orange"
      @click.prevent="shuffle"
      v-if="selectedSongs.length < 2">
      <i class="fa fa-random"></i> All
    </button>

    <button class="play-shuffle btn btn-orange"
      @click.prevent="shuffleSelected"
      v-if="selectedSongs.length > 1">
      <i class="fa fa-random"></i> Selected
    </button>

    <button class="btn btn-green"
      @click.prevent.stop="showingAddToMenu = !showingAddToMenu"
      v-if="selectedSongs.length">
      {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
    </button>

    <button class="btn btn-red"
      @click.prevent="clearQueue"
      v-if="showClearQueueButton">
      Clear
    </button>

    <button class="del btn btn-red" v-if="showDeletePlaylistButton"
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
import { assign } from 'lodash';
import { queueStore } from '../../stores';
import addToMenu from './add-to-menu.vue';

export default {
  name: 'shared--song-list-controls',
  props: ['config', 'selectedSongs'],

  components: { addToMenu },

  data() {
    return {
      fullConfig: {
        shuffle: true,
        addTo: {
          queue: true,
          favorites: true,
          playlists: true,
          newPlaylist: true,
        },
        clearQueue: false,
        deletePlaylist: false
      },
      showingAddToMenu: false,
      numberOfQueuedSongs: 0
    };
  },

  computed: {
    showClearQueueButton() {
      return this.fullConfig.clearQueue;
    },

    showDeletePlaylistButton() {
      return this.fullConfig.deletePlaylist;
    },
  },

  mounted() {
    this.mergeConfig();
    this.$watch('config', this.mergeConfig, { deep: true });
  },

  methods: {
    mergeConfig() {
      this.fullConfig = assign(this.fullConfig, this.config);
    },

    shuffle() {
      this.$emit('shuffleAll');
    },

    shuffleSelected() {
      this.$emit('shuffleSelected');
    },

    clearQueue() {
      this.$emit('clearQueue');
    },

    deletePlaylist() {
      this.$emit('deletePlaylist');
    },

    closeAddToMenu() {
      this.showingAddToMenu = false;
    }
  }
};
</script>

<style lang="sass"></style>
