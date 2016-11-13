<template>
  <article id="albumInfo" :class="mode">
    <h1 class="name">
      <span>{{ album.name }}</span>

      <a class="shuffle" @click.prevent="shuffleAll"><i class="fa fa-random"></i></a>
    </h1>

    <div v-if="album.info">
      <img v-if="album.info.image" :src="album.info.image" class="cover">

      <div class="wiki" v-if="album.info.wiki && album.info.wiki.summary">
        <div class="summary" v-show="showSummary" v-html="album.info.wiki.summary"/>
        <div class="full" v-show="showFull" v-html="album.info.wiki.full"/>

        <button class="more" v-show="showSummary" @click.prevent="showingFullWiki = true">
          Full Wiki
        </button>
      </div>

      <section class="track-listing" v-if="album.info.tracks.length">
        <h1>Track Listing</h1>
        <ul class="tracks">
          <li v-for="(track, idx) in album.info.tracks">
            <span class="no">{{ idx + 1 }}</span>
            <span class="title">{{ track.title }}</span>
            <span class="length">{{ track.fmtLength }}</span>
          </li>
        </ul>
      </section>

      <footer>Data &copy; <a target="_blank" :href="album.info.url">Last.fm</a></footer>
    </div>

    <p class="none" v-else>No album information found.</p>
  </article>
</template>

<script>
import { playback } from '../../../services';

export default {
  props: ['album', 'mode'],

  data() {
    return {
      showingFullWiki: false,
    };
  },

  watch: {
    album() {
      this.showingFullWiki = false;
    },
  },

  computed: {
    showSummary() {
      return this.mode !== 'full' && !this.showingFullWiki;
    },

    showFull() {
      return this.mode === 'full' || this.showingFullWiki;
    },
  },

  methods: {
    /**
     * Shuffle all songs in the current album.
     */
    shuffleAll() {
      playback.playAllInAlbum(this.album);
    },
  },
};
</script>

<style lang="sass">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#albumInfo {
  @include artist-album-info();
}
</style>
