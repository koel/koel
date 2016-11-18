<template>
  <section id="favoritesWrapper">
    <h1 class="heading">
      <span>Songs You Love
        <controls-toggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

        <span class="meta" v-show="meta.songCount">
          {{ meta.songCount | pluralize('song') }}
          •
          {{ meta.totalLength }}
          <template v-if="sharedState.allowDownload && state.songs.length">
            •
            <a href @click.prevent="download" title="Download all songs in playlist">
              Download
            </a>
          </template>
        </span>
      </span>

      <song-list-controls
        v-show="state.songs.length && (!isPhone || showingControls)"
        :config="songListControlConfig"
        :selectedSongs="selectedSongs"
      />
    </h1>

    <song-list v-show="state.songs.length" :items="state.songs" type="favorites"/>

    <div v-show="!state.songs.length" class="none">
      Start loving!
      Click the <i style="margin: 0 5px" class="fa fa-heart"></i> icon when a song is playing to add it
      to this list.
    </div>
  </section>
</template>

<script>
import isMobile from 'ismobilejs';

import { pluralize } from '../../../utils';
import { favoriteStore, sharedStore } from '../../../stores';
import { playback, download } from '../../../services';
import hasSongList from '../../../mixins/has-song-list';

export default {
  name: 'main-wrapper--main-content--favorites',
  mixins: [hasSongList],
  filters: { pluralize },

  data () {
    return {
      state: favoriteStore.state,
      sharedState: sharedStore.state,
    };
  },

  methods: {
    /**
     * Download all favorite songs.
     */
    download() {
      download.fromFavorites();
    },
  },
};
</script>

<style lang="sass">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#favoritesWrapper {
  .none {
    color: $color2ndText;
    padding: 16px 24px;

    a {
      color: $colorHighlight;
    }
  }
}
</style>
