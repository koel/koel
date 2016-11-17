<template>
  <section id="playlistWrapper">
    <h1 class="heading">
      <span>{{ playlist.name }}
        <i class="fa fa-angle-down toggler" v-show="isPhone && !showingControls" @click="showingControls = true"/>
        <i class="fa fa-angle-up toggler" v-show="isPhone && showingControls" @click.prevent="showingControls = false"/>

        <span class="meta" v-show="meta.songCount">
          {{ meta.songCount | pluralize('song') }}
          •
          {{ meta.totalLength }}
          <template v-if="sharedState.allowDownload && playlist.songs.length">
            •
            <a href @click.prevent="download" title="Download all songs in playlist">
              Download
            </a>
          </template>
        </span>
      </span>

      <song-list-controls
        v-show="playlist.songs.length"
        @shuffleAll="shuffleAll"
        @shuffleSelected="shuffleSelected"
        @deletePlaylist="confirmDelete"
        :config="songListControlConfig"
        :selectedSongs="selectedSongs"
      />
    </h1>

    <song-list v-show="playlist.songs.length" :items="playlist.songs" :playlist="playlist" type="playlist"/>

    <div v-show="!playlist.songs.length" class="none">
      The playlist is currently empty. You can fill it up by dragging songs into its name in the sidebar,
      or use the &quot;Add To…&quot; button.
    </div>
  </section>
</template>

<script>
import isMobile from 'ismobilejs';
import swal from 'sweetalert';

import { pluralize, event } from '../../../utils';
import { playlistStore, sharedStore } from '../../../stores';
import { playback, download } from '../../../services';
import router from '../../../router';
import hasSongList from '../../../mixins/has-song-list';

export default {
  name: 'main-wrapper--main-content--playlist',
  mixins: [hasSongList],
  filters: { pluralize },

  data() {
    return {
      playlist: playlistStore.stub,
      sharedState: sharedStore.state,
      songListControlConfig: {
        deletePlaylist: true,
      },
    };
  },

  created() {
    /**
     * Listen to 'main-content-view:load' event to load the requested
     * playlist into view if applicable.
     *
     * @param {String} view   The view's name.
     * @param {Object} playlist
     */
    event.on('main-content-view:load', (view, playlist) => {
      if (view === 'playlist') {
        this.playlist = playlist;
      }
    });
  },

  methods: {
    /**
     * Shuffle the songs in the current playlist.
     * Overriding the mixin.
     */
    shuffleAll() {
      playback.queueAndPlay(this.playlist.songs, true);
    },

    /**
     * Confirm deleting the playlist.
     */
    confirmDelete() {
      // If the playlist is empty, just go ahead and delete it.
      if (!this.playlist.songs.length) {
        this.del();

        return;
      }

      swal({
        title: 'Are you sure?',
        text: 'Once it’s gone, it’s gone, and there’s no turning back.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, go ahead',
      }, this.del);
    },

    /**
     * Delete the current playlist.
     */
    del() {
      playlistStore.delete(this.playlist).then(() => {
        // Reset the current playlist to our stub, so that we don't encounter
        // any property reference error.
        this.playlist = playlistStore.stub;

        // Switch back to Home screen
        router.go('home');
      });
    },

    /**
     * Download all songs in the current playlist.
     */
    download() {
      return download.fromPlaylist(this.playlist);
    },
  },
};
</script>

<style lang="sass">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#playlistWrapper {
  button.play-shuffle, button.del {
    i {
      margin-right: 0 !important;
    }
  }

  .none {
    color: $color2ndText;
    padding: 16px 24px;

    a {
      color: $colorHighlight;
    }
  }
}
</style>
