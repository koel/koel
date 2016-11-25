<template>
  <article class="item" v-if="genre.songs.length" draggable="true" @dragstart="dragStart">
    <span class="cover" :style="{ backgroundImage: 'url('+genre.image+')' }">
      <a class="control" @click.prevent="play">
        <i class="fa fa-play"></i>
      </a>
    </span>
    <footer>
      <div class="info">
        <a class="name" :href="'/#!/genre/'+genre.id">{{ genre.name }}</a>
      </div>
      <p class="meta">
        <span class="left">
          {{ genre.songs.length | pluralize('song') }}
          •
          {{ genre.fmtLength }}
          •
          {{ genre.playCount | pluralize('play') }}
        </span>
        <span class="right">
          <a href @click.prevent="shuffle" title="Shuffle">
            <i class="fa fa-random"></i>
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
import { queueStore, sharedStore } from '../../stores';
import { playback, download } from '../../services';

export default {
  name: 'shared--genre-item',
  props: ['genre'],
  filters: { pluralize },

  data() {
    return {
      sharedState: sharedStore.state,
    };
  },

  computed: { },

  methods: {
    /**
     * Play all songs in the current genre in track order,
     * or queue them up if Ctrl/Cmd key is pressed.
     */
    play(e) {
      if (e.metaKey || e.ctrlKey) {
        queueStore.queue(this.genre.songs);
      } else {
        playback.playAllInAlbum(this.genre, false);
      }
    },

    /**
     * Shuffle all songs in genre.
     */
    shuffle() {
      playback.playAllInGenre(this.genre, true);
    },

    /**
     * Allow dragging the genre (actually, its songs).
     */
    dragStart(e) {
      const songIds = map(this.genre.songs, 'id');
      e.dataTransfer.setData('application/x-koel.text+plain', songIds);
      e.dataTransfer.effectAllowed = 'move';

      // Set a fancy drop image using our ghost element.
      const $ghost = $('#dragGhost').text(`All ${songIds.length} song${songIds.length === 1 ? '' : 's'} in ${this.genre.name}`);
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
