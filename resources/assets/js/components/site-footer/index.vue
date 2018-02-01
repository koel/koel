<template>
  <footer id="mainFooter">
    <div class="side player-controls" id="playerControls">
      <i class="prev fa fa-step-backward control" @click.prevent="playPrev"/>

      <span class="play control" v-if="song.playbackState !== 'playing'" @click.prevent="resume">
        <i class="fa fa-play"></i>
      </span>
      <span class="pause control" v-else @click.prevent="pause">
        <i class="fa fa-pause"></i>
      </span>

      <i class="next fa fa-step-forward control" @click.prevent="playNext"/>
    </div>

    <div class="media-info-wrap">
      <div class="middle-pane">
        <span class="album-thumb" v-if="cover" :style="{ backgroundImage: 'url('+cover+')' }"/>

        <div class="progress" id="progressPane">
          <h3 class="title">{{ song.title }}</h3>
          <p class="meta">
            <a class="artist" :href="`#!/artist/${song.artist.id}`">{{ song.artist.name }}</a> –
            <a class="album" :href="`#!/album/${song.album.id}`">{{ song.album.name }}</a>
          </p>

          <div class="plyr">
            <audio crossorigin="anonymous" controls></audio>
          </div>
        </div>
      </div>

      <div class="other-controls" :class="{ 'with-gradient': prefs.showExtraPanel }">
        <div class="wrapper" v-koel-clickaway="closeEqualizer">
          <equalizer v-if="useEqualizer" v-show="showEqualizer"/>
          <sound-bar v-show="song.playbackState === 'playing'"/>
          <i v-if="song.id"
            class="like control fa fa-heart"
            :class="{ liked: song.liked }"
            @click.prevent="like"/>
          <span class="control info"
            @click.prevent="toggleExtraPanel"
            :class="{ active: prefs.showExtraPanel }">Info</span>
          <i class="fa fa-sliders control equalizer"
            v-if="useEqualizer"
            @click="showEqualizer = !showEqualizer"
            :class="{ active: showEqualizer }"/>
          <a v-else class="queue control" :class="{ active: viewingQueue }" href="#!/queue">
            <i class="fa fa-list-ol"></i>
          </a>
          <span class="repeat control" :class="prefs.repeatMode" @click.prevent="changeRepeatMode">
            <i class="fa fa-repeat"></i>
          </span>
          <volume/>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
import { playback, socket } from '@/services'
import { isAudioContextSupported, event } from '@/utils'
import { songStore, favoriteStore, preferenceStore } from '@/stores'

import soundBar from '../shared/sound-bar.vue'
import equalizer from './equalizer.vue'
import volume from './volume.vue'

export default {
  data () {
    return {
      song: songStore.stub,
      viewingQueue: false,

      prefs: preferenceStore.state,
      showEqualizer: false,
      cover: null,

      /**
       * Indicate if we should build and use an equalizer.
       *
       * @type {Boolean}
       */
      useEqualizer: isAudioContextSupported()
    }
  },

  components: { soundBar, equalizer, volume },

  computed: {
    /**
     * Get the previous song in queue.
     *
     * @return {?Object}
     */
    prev () {
      return playback.previous
    },

    /**
     * Get the next song in queue.
     *
     * @return {?Object}
     */
    next () {
      return playback.next
    }
  },

  methods: {
    /**
     * Play the previous song in queue.
     */
    playPrev () {
      return playback.playPrev()
    },

    /**
     * Play the next song in queue.
     */
    playNext () {
      return playback.playNext()
    },

    /**
     * Resume the current song.
     * If the current song is the stub, just play the first song in the queue.
     */
    resume () {
      this.song.id ? playback.resume() : playback.playFirstInQueue()
    },

    /**
     * Pause the playback.
     */
    pause () {
      playback.pause()
    },

    /**
     * Change the repeat mode.
     */
    changeRepeatMode () {
      return playback.changeRepeatMode()
    },

    /**
     * Like the current song.
     */
    like () {
      if (this.song.id) {
        favoriteStore.toggleOne(this.song)
        socket.broadcast('song', songStore.generateDataToBroadcast(this.song))
      }
    },

    /**
     * Toggle hide or show the extra panel.
     */
    toggleExtraPanel () {
      preferenceStore.set('showExtraPanel', !this.prefs.showExtraPanel)
    },

    closeEqualizer () {
      this.showEqualizer = false
    }
  },

  created () {
    event.on({
      /**
       * Listen to song:played event to set the current playing song and the cover image.
       *
       * @param  {Object} song
       *
       * @return {Boolean}
       */
      'song:played': song => {
        this.song = song
        this.cover = this.song.album.cover
      },

      /**
       * Listen to main-content-view:load event and highlight the Queue icon if
       * the Queue screen is being loaded.
       */
      'main-content-view:load': view => {
        this.viewingQueue = view === 'queue'
      }
    })
  }
}
</script>

<style lang="scss">
@import "~#/partials/_vars.scss";
@import "~#/partials/_mixins.scss";

@mixin hasSoftGradientOnTop($startColor) {
  position: relative;

  // Add a reverse gradient here to elimate the "hard cut" feel when the
  // song list is too long.
  &::before {
    $gradientHeight: 2*$footerHeight/3;
    content: " ";
    position: absolute;
    width: 100%;
    height: $gradientHeight;
    top: -$gradientHeight;
    left: 0;

    // Safari 8 won't recognize rgba(255, 255, 255, 0) and treat it as black.
    // rgba($startColor, 0) is a workaround.
    background-image: linear-gradient(to bottom, rgba($startColor, 0) 0%, rgba($startColor, 1) 100%);
    pointer-events: none; // click-through
  }
}

#mainFooter {
  background: $color2ndBgr;
  position: fixed;
  width: 100%;
  height: $footerHeight;
  bottom: 0;
  left: 0;
  border-top: 1px solid $colorMainBgr;

  display: flex;
  flex: 1;
  z-index: 1000;

  .media-info-wrap {
    flex: 1;
    display: flex;
  }

  .other-controls {
    @include vertical-center();
    @include hasSoftGradientOnTop($colorMainBgr);

    &.with-gradient {
      @include hasSoftGradientOnTop($colorExtraBgr);
    }

    text-transform: uppercase;
    flex: 0 0 $extraPanelWidth;
    color: $colorLink;

    .wrapper {
      display: inline-table;
    }

    .control {
      display: inline-block;
      padding: 0 8px;

      &.active {
        color: $colorHighlight;
      }

      &:last-child {
        padding-right: 0;
      }
    }

    .repeat {
      position: relative;

      &.REPEAT_ALL, &.REPEAT_ONE {
        color: $colorHighlight;
      }

      &.REPEAT_ONE::after {
        content: "1";
        position: absolute;
        top: 0;
        left: 0;
        font-weight: 700;
        font-size: .5rem;
        text-align: center;
        width: 100%;
      }
    }

    .like {
      &:hover {
      }

      &.liked {
        color: $colorHeart;
      }
    }

    @media only screen and (max-width: 768px) {
      position: absolute !important;
      right: 0;
      top: 0;
      height: 100%;
      width: 188px;

      &::before {
        display: none;
      }

      .queue {
        display: none;
      }

      .control {
        padding: 0 8px;
      }
    }
  }

  @media only screen and (max-width: 768px) {
    height: $footerHeightMobile;
  }
}

#playerControls {
  @include vertical-center();
  flex: 0 0 256px;
  font-size: 1.8rem;
  background: $colorPlayerControlsBgr;

  @include hasSoftGradientOnTop($colorSidebarBgr);

  .prev, .next {
    transition: .3s;
  }

  .play, .pause {
    font-size: 2rem;
    display: inline-block;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    line-height: 40px;
    text-align: center;
    border: 1px solid #a0a0a0;
    margin: 0 16px;
    text-indent: 2px;
  }

  .pause {
    text-indent: 0;
    font-size: 18px;
  }

  .enabled {
    opacity: 1;
  }


  @media only screen and (max-width: 768px) {
    flex: 1;

    &::before {
      display: none;
    }
  }
}

.middle-pane {
  flex: 1;
  display: flex;

  .album-thumb {
    flex: 0 0 $footerHeight;
    height: $footerHeight;
    background: url("~#/../img/covers/unknown-album.png");
    background-size: cover;
    position: relative;
  }

  @include hasSoftGradientOnTop($colorMainBgr);

  @media only screen and (max-width: 768px) {
    width: 100%;
    position: absolute;
    top: 0;
    left: 0;
    height: 8px;

    .album-thumb {
      display: none;
    }

    ::before {
      display: none;
    }
  }
}

#progressPane {
  flex: 1;
  text-align: center;
  padding-top: 16px;
  line-height: 18px;
  background: rgba(1, 1, 1, .2);
  position: relative;

  .meta {
    font-size: .9rem;

    a {
      &:hover {
        color: $colorHighlight;
      }
    }
  }

  // Some little tweaks here and there
  .plyr {
    width: 100%;
    position: absolute;
    top: 0;
    left: 0;
  }

  .plyr__progress {
    overflow: hidden;
    height: 1px;

    html.touch &, .middle-pane:hover & {
      overflow: visible;
      height: $plyr-volume-track-height;
    }
  }

  .plyr__controls {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    padding: 0;
  }

  .plyr__controls--left, .plyr__controls--right {
    display: none;
  }


  @media only screen and (max-width: 768px) {
    .meta, .title {
      display: none;
    }

    top: -15px;
    padding-top: 0;
    width: 100%;
    position: absolute;

    .plyr {
      &__progress {
        height: 16px;

        &--buffer[value],
        &--played[value],
        &--seek[type='range'] {
          height: 16px;
        }
      }
    }
  }
}
</style>
