<template>
  <li class="song-item-home"
    :class="{ playing: song.playbackState === 'playing' || song.playbackState === 'paused' }"
    @dblclick.prevent="play"
  >
    <span class="cover" :style="{ backgroundImage: 'url('+song.album.cover+')' }">
      <a class="control" @click.prevent="changeSongState">
        <i class="fa fa-play" v-if="song.playbackState !== 'playing'"></i>
        <i class="fa fa-pause" v-else></i>
      </a>
    </span>
    <span class="details">
      <span v-if="showPlayCount" :style="{ width: song.playCount*100/topPlayCount+'%' }" class="play-count"/>
      {{ song.title }}
      <span class="by">
        <a :href="'/#!/artist/'+song.artist.id">{{ song.artist.name }}</a>
        <template v-if="showPlayCount">- {{ song.playCount | pluralize('play') }}</template>
      </span>
    </span>
  </li>
</template>

<script>
import { pluralize } from '../../utils'
import { queueStore } from '../../stores'
import { playback } from '../../services'

export default {
  name: 'shared--home-song-item',
  props: ['song', 'topPlayCount'],
  filters: { pluralize },

  computed: {
    showPlayCount () {
      return this.topPlayCount && this.song.playCount
    }
  },

  methods: {
    play () {
      queueStore.contains(this.song) || queueStore.queueAfterCurrent(this.song)
      playback.play(this.song)
    },

    changeSongState () {
      if (this.song.playbackState === 'stopped') {
        this.play(this.song)
      } else if (this.song.playbackState === 'paused') {
        playback.resume()
      } else {
        playback.pause()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

.song-item-home {
  display: flex;

  &.playing {
    color: $colorHighlight;
  }

  &:hover .cover {
    .control {
      display: block;
    }

    &::before {
      opacity: .7;
    }
  }

  .cover {
    flex: 0 0 48px;
    height: 48px;
    background-size: cover;
    position: relative;

    @include vertical-center();

    &::before {
      content: " ";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      background: #000;
      opacity: 0;

      html.touchevents & {
        opacity: .7;
      }
    }

    .control {
      border-radius: 50%;
      width: 28px;
      height: 28px;
      background: rgba(0, 0, 0, .7);
      border: 1px solid transparent;
      line-height: 2rem;
      font-size: 1rem;
      text-align: center;
      z-index: 1;
      display: none;
      color: #fff;
      transition: .3s;

      &:hover {
        transform: scale(1.2);
        border-color: #fff;
      }

      html.touchevents & {
        display: block;
      }
    }
  }

  .details {
    flex: 1;
    padding: 4px 8px;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;

    .play-count {
      background: rgba(255, 255, 255, 0.08);
      position: absolute;
      height: 100%;
      top: 0;
      left: 0;
      pointer-events: none;
    }

    .by {
      display: block;
      font-size: .9rem;
      margin-top: 2px;
      color: $color2ndText;
      opacity: .8;

      a {
        color: #fff;

        &:hover {
          color: $colorHighlight;
        }
      }
    }
  }

  margin-bottom: 8px;
}
</style>
