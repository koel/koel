<template>
  <div class="add-to" v-show="showing" v-koel-clickaway="close">
    <p>Add {{ songs.length | pluralize('song') }} to</p>

    <ul>
      <li class="after-current" @click="queueSongsAfterCurrent">After Current Song</li>
      <li class="bottom-queue" @click="queueSongsToBottom">Bottom of Queue</li>
      <li class="top-queue" @click="queueSongsToTop">Top of Queue</li>
      <li class="favorites" v-if="config.favorites" @click="addSongsToFavorite">Favorites</li>
      <li class="playlist" v-for="playlist in playlistState.playlists"
        @click="addSongsToExistingPlaylist(playlist)">{{ playlist.name }}</li>
    </ul>

    <p>or create a new playlist</p>

    <form class="form-save form-simple" @submit.prevent="createNewPlaylistFromSongs">
      <input type="text"
        @keyup.esc.prevent="close"
        v-model="newPlaylistName"
        placeholder="Playlist name"
        required>
      <button type="submit">
        <i class="fa fa-save"></i>
      </button>
    </form>
  </div>
</template>

<script>
import { assign } from 'lodash';

import { pluralize, event } from '../../utils';
import { playlistStore } from '../../stores';
import router from '../../router';
import songMenuMethods from '../../mixins/song-menu-methods';

export default {
  name: 'shared--add-to-menu',
  props: ['songs', 'showing', 'config'],
  mixins: [songMenuMethods],
  filters: { pluralize },

  data() {
    return {
      newPlaylistName: '',
      playlistState: playlistStore.state,
    };
  },

  watch: {
    songs() {
      if (!this.songs.length) {
        this.close();
      }
    },
  },

  methods: {
    /**
     * Save the selected songs as a playlist.
     * As of current we don't have selective save.
     */
    createNewPlaylistFromSongs() {
      this.newPlaylistName = this.newPlaylistName.trim();

      if (!this.newPlaylistName) {
        return;
      }

      playlistStore.store(this.newPlaylistName, this.songs).then(p => {
        this.newPlaylistName = '';
        // Activate the new playlist right away
        this.$nextTick(() => router.go(`playlist/${p.id}`));
      });

      this.close();
    },

    close() {
      this.$parent.closeAddToMenu();
    },
  },
};
</script>

<style lang="sass" scoped>
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

.add-to {
  @include context-menu();

  position: absolute;
  padding: 8px;
  top: 36px;
  left: 0;
  width: 100%;

  p {
    margin: 4px 0;
    font-size: .9rem;

    &::first-of-type {
      margin-top: 0;
    }
  }

  $itemHeight: 28px;
  $itemMargin: 2px;

  ul {
    max-height: 5 * ($itemHeight + $itemMargin);
    overflow-y: scroll;
    -webkit-overflow-scrolling: touch;
  }

  li {
    background: rgba(255, 255, 255, .2);
    height: $itemHeight;
    line-height: $itemHeight;
    padding: 0 8px;
    margin: $itemMargin 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    border-radius: 3px;
    background: #fff;

    &:hover {
      background: $colorHighlight;
      color: #fff;
    }
  }

  &::before {
    display: block;
    content: " ";
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-bottom: 10px solid rgb(232, 232, 232);
    position: absolute;
    top: -7px;
    left: calc(50% - 10px);
  }

  form {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;

    input[type="text"] {
      width: 100%;
      border-radius: 5px 0 0 5px;
      height: 28px;
    }

    button[type="submit"] {
      margin-top: 0;
      border-radius: 0 5px 5px 0 !important;
      height: 28px;
      margin-left: -2px !important;
    }
  }
}
</style>
