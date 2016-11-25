<template>
  <section id="genreWrapper">
    <h1 class="heading">
      <span class="overview">
        <img :src="genre.image" width="64" height="64" class="cover">
        {{ genre.name }}
        <i class="fa fa-angle-down toggler" v-show="isPhone && !showingControls" @click="showingControls = true"/>
        <i class="fa fa-angle-up toggler" v-show="isPhone && showingControls" @click.prevent="showingControls = false"/>

        <span class="meta" v-show="meta.songCount">
          {{ meta.songCount | pluralize('song') }}
          •
          {{ meta.totalLength }}
        </span>
      </span>

      <div class="buttons" v-show="!isPhone || showingControls">
        <button class="play-shuffle btn btn-orange" @click.prevent="shuffle" v-if="selectedSongs.length < 2">
          <i class="fa fa-random"></i> All
        </button>
        <button class="play-shuffle btn btn-orange" @click.prevent="shuffleSelected" v-if="selectedSongs.length > 1">
          <i class="fa fa-random"></i> Selected
        </button>
        <button class="btn btn-green" @click.prevent.stop="showingAddToMenu = !showingAddToMenu" v-if="selectedSongs.length">
          {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
        </button>

        <add-to-menu :songs="selectedSongs" :showing="showingAddToMenu"/>
      </div>
    </h1>

    <song-list :items="genre.songs" type="genre"/>

  </section>
</template>

<script>
import isMobile from 'ismobilejs';

import { pluralize, event } from '../../../utils';
import { genreStore, songStore, sharedStore } from '../../../stores';
import { playback } from '../../../services';
import router from '../../../router';
import hasSongList from '../../../mixins/has-song-list';

export default {
  name: 'main-wrapper--main-content--genre',
  mixins: [hasSongList],
  components: { },
  filters: { pluralize },

  data() {
    return {
      sharedState: sharedStore.state,
      genre: genreStore.stub,
      isPhone: isMobile.phone,
      showingControls: false,
    };
  },

  computed: {
  },

  watch: {
    /**
     * Watch the genre's song count.
     * If this is changed to 0, the user has edit the songs in this genre
     * and move all of them into another genre.
     * We should then go back to the genre list.
     */
    'genre.songs.length' (newVal) {
      if (!newVal) {
        router.go('genres');
      }
    },
  },

  created() {
    /**
     * Listen to 'main-content-view:load' event to load the requested genre
     * into view if applicable.
     *
     * @param {String} view   The view name
     * @param {Object} genre  The genre object
     */
    event.on('main-content-view:load', (view, genre) => {
      if (view === 'genre') {
        this.genre = genre;
      }
    });
  },

  methods: {
    /**
     * Shuffle the songs in the current genre.
     */
    shuffle() {
      playback.queueAndPlay(this.genre.songs, true);
    },
  },
};
</script>

<style lang="sass" scoped>
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#genreWrapper {
  button.play-shuffle {
    i {
      margin-right: 0 !important;
    }
  }

  .heading {
    .overview {
      position: relative;
      padding-left: 84px;

      @media only screen and (max-width : 768px) {
        padding-left: 0;
      }
    }

    .cover {
      position: absolute;
      left: 0;
      top: -7px;

      @media only screen and (max-width : 768px) {
        display: none;
      }
    }
  }
}
</style>
