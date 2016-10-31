<template>
  <article class="item" v-if="album.songs.length" draggable="true" @dragstart="dragStart">
    <span class="cover" :style="{ backgroundImage: 'url(' + album.cover + ')' }">
      <a class="control" @click.prevent="play">
        <i class="fa fa-play"></i>
      </a>
    </span>
    <footer>
      <div class="info">
        <a class="name" :href="'/#!/album/' + album.id">{{ album.name }}</a>
        <span class="sep">by</span>
        <a class="artist" v-if="isNormalArtist" :href="'/#!/artist/' + album.artist.id">{{ album.artist.name }}</a>
        <span class="artist nope" v-else>{{ album.artist.name }}</span>
      </div>
      <p class="meta">
        <span class="left">
          {{ album.songs.length | pluralize('song') }}
          •
          {{ album.fmtLength }}
          •
          {{ album.playCount | pluralize('play') }}
        </span>
        <span class="right">
          <a href @click.prevent="shuffle" title="Shuffle">
            <i class="fa fa-random"></i>
          </a>
          <a href @click.prevent="download" v-if="sharedState.allowDownload" title="Download all songs in album">
            <i class="fa fa-download"></i>
          </a>
        </span>
      </p>
    </footer>
  </article>
</template>

<script>
import { map } from 'lodash';
import $ from 'jquery';

import { pluralize } from '../../utils';
import { queueStore, artistStore, sharedStore } from '../../stores';
import { playback, download } from '../../services';

export default {
  name: 'shared--album-item',
  props: ['album'],
  filters: { pluralize },

  data() {
    return {
      sharedState: sharedStore.state,
    };
  },

  computed: {
    isNormalArtist() {
      return !artistStore.isVariousArtists(this.album.artist)
        && !artistStore.isUnknownArtist(this.album.artist);
    },
  },

  methods: {
    /**
     * Play all songs in the current album in track order,
     * or queue them up if Ctrl/Cmd key is pressed.
     */
    play(e) {
      if (e.metaKey || e.ctrlKey) {
        queueStore.queue(this.album.songs);
      } else {
        playback.playAllInAlbum(this.album, false);
      }
    },

    /**
     * Shuffle all songs in album.
     */
    shuffle() {
      playback.playAllInAlbum(this.album, true);
    },

    /**
     * Download all songs in album.
     */
    download() {
      download.fromAlbum(this.album);
    },

    /**
     * Allow dragging the album (actually, its songs).
     */
    dragStart(e) {
      const songIds = map(this.album.songs, 'id');
      e.dataTransfer.setData('application/x-koel.text+plain', songIds);
      e.dataTransfer.effectAllowed = 'move';

      // Set a fancy drop image using our ghost element.
      const $ghost = $('#dragGhost').text(`All ${songIds.length} song${songIds.length === 1 ? '' : 's'} in ${this.album.name}`);
      e.dataTransfer.setDragImage($ghost[0], 0, 0);
    },
  },
};
</script>

<style lang="sass">
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

@include artist-album-card();

.sep {
  display: none;
  color: $color2ndText;

  .as-list & {
    display: inline;
  }
}
</style>
