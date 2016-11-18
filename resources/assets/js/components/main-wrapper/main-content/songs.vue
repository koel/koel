<template>
  <section id="songsWrapper">
    <h1 class="heading">
      <span>All Songs
        <controls-toggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

        <span class="meta" v-show="meta.songCount">
          {{ meta.songCount | pluralize('song') }}
          •
          {{ meta.totalLength }}
        </span>
      </span>

      <song-list-controls
        v-show="state.songs.length && (!isPhone || showingControls)"
        @shuffleAll="shuffleAll"
        @shuffleSelected="shuffleSelected"
        :config="songListControlConfig"
        :selectedSongs="selectedSongs"
      />
    </h1>

    <song-list :items="state.songs" type="allSongs"/>
  </section>
</template>

<script>
import isMobile from 'ismobilejs';

import { pluralize } from '../../../utils';
import { songStore } from '../../../stores';
import { playback } from '../../../services';
import hasSongList from '../../../mixins/has-song-list';

export default {
  name: 'main-wrapper--main-content--songs',
  mixins: [hasSongList],
  filters: { pluralize },

  data() {
    return {
      state: songStore.state
    };
  },
};
</script>

<style lang="sass">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";
</style>

