<template>
  <section id="queueWrapper">
    <h1 class="heading">
      <span title="That's a freaking lot of U's and E's">当前播放队列
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
        @clearQueue="clearQueue"
        :config="songListControlConfig"
        :selectedSongs="selectedSongs"
      />
    </h1>

    <song-list v-show="state.songs.length" :items="state.songs" :sortable="false" type="queue"/>

    <div v-show="!state.songs.length" class="none">
      <p>别看了,这里什么也没有.</p>

      <p v-if="showShufflingAllOption">试试
        <a class="start" @click.prevent="shuffleAll">刷新所有歌曲</a>?
      </p>
    </div>
  </section>
</template>

<script>
import { pluralize } from '../../../utils'
import { queueStore, songStore } from '../../../stores'
import { playback } from '../../../services'
import hasSongList from '../../../mixins/has-song-list'

export default {
  name: 'main-wrapper--main-content--queue',
  mixins: [hasSongList],
  filters: { pluralize },

  data () {
    return {
      state: queueStore.state,
      songListControlConfig: {
        clearQueue: true
      }
    }
  },

  computed: {
    /**
     * Determine if we should display a "Shuffle All" link.
     */
    showShufflingAllOption () {
      return songStore.all.length
    }
  },

  methods: {
    /**
     * Shuffle all songs we have.
     * Overriding the mixin.
     */
    shuffleAll () {
      playback.queueAndPlay(this.state.songs.length ? this.state.songs : songStore.all, true)
    },

    /**
     * Clear the queue.
     */
    clearQueue () {
      queueStore.clear()
    }
  }
}
</script>

<style lang="sass">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#queueWrapper {
  .none {
    color: $color2ndText;
    padding: 16px 24px;

    a {
      color: $colorHighlight;
    }
  }

  button.play-shuffle {
    i {
      margin-right: 0 !important;
    }
  }
}
</style>
