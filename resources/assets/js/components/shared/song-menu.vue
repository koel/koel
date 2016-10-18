<template>
  <ul ref="menu" class="menu song-menu" v-show="shown" tabindex="-1" @contextmenu.prevent
    @blur="close"
    :style="{ top: top + 'px', left: left + 'px' }"
  >
    <template v-show="onlyOneSongSelected">
      <li @click="doPlayback">
        <span v-show="!firstSongPlaying">Play</span>
        <span v-show="firstSongPlaying">Pause</span>
      </li>
      <li @click="viewAlbumDetails(songs[0].album)">Go to Album</li>
      <li @click="viewArtistDetails(songs[0].artist)">Go to Artist</li>
    </template>
    <li class="has-sub">Add To
      <ul class="menu submenu">
        <li @click="queueSongsAfterCurrent">After Current Song</li>
        <li @click="queueSongsToBottom">Bottom of Queue</li>
        <li @click="queueSongsToTop">Top of Queue</li>
        <li class="separator"></li>
        <li @click="addSongsToFavorite">Favorites</li>
        <li class="separator" v-show="playlistState.playlists.length"></li>
        <li v-for="p in playlistState.playlists" @click="addSongsToExistingPlaylist(p)">{{ p.name }}</li>
      </ul>
    </li>
    <li v-show="isAdmin" @click="openEditForm">Edit</li>
    <li v-show="sharedState.allowDownload" @click="download">Download</li>
    <!-- somehow v-if doesn't work here -->
    <li v-show="copyable && onlyOneSongSelected" @click="copyUrl">Copy Shareable URL</li>
  </ul>
</template>

<script>
import $ from 'jquery';

import songMenuMethods from '../../mixins/song-menu-methods';

import { event, isClipboardSupported, copyText } from '../../utils';
import { sharedStore, songStore, queueStore, userStore, playlistStore } from '../../stores';
import { playback, download } from '../../services';
import router from '../../router';

export default {
  name: 'song-menu',
  props: ['songs'],
  mixins: [songMenuMethods],

  data() {
    return {
      playlistState: playlistStore.state,
      sharedState: sharedStore.state,
      copyable: isClipboardSupported(),
    };
  },

  computed: {
    onlyOneSongSelected() {
      return this.songs.length === 1;
    },

    firstSongPlaying() {
      return this.songs[0] ? this.songs[0].playbackState === 'playing' : false;
    },

    isAdmin() {
      return userStore.current.is_admin;
    },
  },

  methods: {
    open(top = 0, left = 0) {
      if (!this.songs.length) {
        return;
      }

      this.top = top;
      this.left = left;
      this.shown = true;

      this.$nextTick(() => {
        // Make sure the menu isn't off-screen
        if (this.$el.getBoundingClientRect().bottom > window.innerHeight) {
          $(this.$el).css({
            top: 'auto',
            bottom: 0,
          });
        } else {
          $(this.$el).css({
            top: this.top,
            bottom: 'auto',
          });
        }

        this.$refs.menu.focus();
      });
    },

    /**
     * Take the right playback action based on the current playback state.
     */
    doPlayback() {
      switch (this.songs[0].playbackState) {
        case 'playing':
          playback.pause();
          break;
        case 'paused':
          playback.resume();
          break;
        default:
          if (!queueStore.contains(this.songs[0])) {
            queueStore.queueAfterCurrent(this.songs[0]);
          }

          playback.play(this.songs[0]);
          break;
      }

      this.close();
    },

    /**
     * Trigger opening the "Edit Song" form/overlay.
     */
    openEditForm() {
      if (this.songs.length) {
        event.emit('songs:edit', this.songs);
      }

      this.close();
    },

    /**
     * Load the album details screen.
     */
    viewAlbumDetails(album) {
      router.go(`album/${album.id}`);
      this.close();
    },

    /**
     * Load the artist details screen.
     */
    viewArtistDetails(artist) {
      router.go(`artist/${artist.id}`);
      this.close();
    },

    download() {
      download.fromSongs(this.songs);
      this.close();
    },

    copyUrl() {
      copyText(songStore.getShareableUrl(this.songs[0]));
    },
  },

  /**
   * On component mounted(), we use some JavaScript to prepare the submenu triggering.
   * With this, we can catch when the submenus shown or hidden, and can make sure
   * they don't appear off-screen.
   */
  mounted() {
    $(this.$el).find('.has-sub').hover(e => {
      const $submenu = $(e.target).find('.submenu:first');
      if (!$submenu.length) {
        return;
      }

      $submenu.show();

      // Make sure the submenu isn't off-screen
      if ($submenu[0].getBoundingClientRect().bottom > window.innerHeight) {
        $submenu.css({
          top: 'auto',
          bottom: 0,
        });
      }
    }, e => {
      $(e.target).find('.submenu:first').hide().css({
        top: 0,
        bottom: 'auto',
      });
    });
  },
};
</script>

<style lang="sass" scoped>
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

.menu {
  @include context-menu();
  position: fixed;

  li {
    position: relative;
    padding: 4px 12px;
    cursor: default;
    white-space: nowrap;

    &:hover {
      background: $colorOrange;
      color: #fff;
    }

    &.separator {
      pointer-event: none;
      padding: 1px 0;
      background: #ccc;
    }

    &.has-sub {
      padding-right: 24px;

      &:after {
        position: absolute;
        right: 12px;
        top: 4px;
        content: "▸";
        width: 16px;
        text-align: right;
      }
    }
  }

  .submenu {
    position: absolute;
    display: none;
    left: 100%;
    top: 0;
  }
}
</style>
