<template>
  <div id="remoteController">
    <p class="collapse">
      <i class="fa fa-angle-down"></i>
    </p>
    <div class="details" v-if="song">
      <div class="translucent" :style="{ backgroundImage: 'url('+song.album.cover+')' }"></div>
      <img :src="song.album.cover" alt="song.album.cover" class="cover">
      <div class="info">
        <div class="wrap">
          <p class="title text">{{ song.title }}</p>
          <p class="artist text">{{ song.artist.name }}</p>
          <p class="album text">{{ song.album.name }}</p>
        </div>
      </div>
    </div>
    <footer>
      <span class="favorite">
        <i class="fa fa-heart" v-if="song && song.liked"></i>
        <i class="fa fa-heart-o" v-else></i>
      </span>
      <span class="prev" @click="playPrev">
        <i class="fa fa-step-backward"></i>
      </span>
      <span class="play-pause" @click="togglePlayback">
        <i class="fa fa-pause" v-if="playing"></i>
        <i class="fa fa-play" v-else></i>
      </span>
      <span class="next" @click="playNext">
        <i class="fa fa-step-forward"></i>
      </span>
      <span class="volume">
        <i class="fa fa-volume-up"></i>
      </span>
    </footer>
  </div>
</template>

<script>
  import { socket } from '../services'

  export default {
    data () {
      return {
        song: null
      }
    },

    methods: {
      togglePlayback () {
        if (this.song) {
          this.song.playbackState = this.song.playbackState === 'playing' ? 'paused' : 'playingp'
        }

        socket.broadcast('playback:toggle')
      },

      playNext () {
        socket.broadcast('playback:next')
      },

      playPrev () {
        socket.broadcast('playback:prev')
      },

      requestForUpdate() {

      }
    },

    computed: {
      playing () {
        return this.song && this.song.playbackState === 'playing'
      }
    },

    created () {
      this.requestForUpdate()

      socket.listen('song:played', ({ song }) => {
        this.song = song
      })
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/assets/sass/partials/_vars.scss";
  @import "resources/assets/sass/partials/_mixins.scss";
  @import "resources/assets/sass/partials/_shared.scss";

  #remoteController {
    background: $colorMainBgr;
    position: fixed;
    z-index: 9999;
    width: 100vw;
    height: 100vh;
    top: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-align: center;

    .collapse {
      height: 12vmin;
      line-height: 12vmin;
      font-size: 5vmin;
    }

    .details {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: space-between;

      .info {
        width: 100%;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-evenly;
      }

      .cover {
        width: calc(100vw - 40px);
        height: auto;
      }

      .text {
        max-width: 90%;
        margin: 0 auto;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        line-height: 1.3;
      }

      .title {
        font-size: 6vmin;
        font-weight: bold;
        margin: 0 auto 15px;
      }

      .artist {
        font-size: 5.5vmin;
        margin: 0 auto 6px;
        font-weight: 100;
        opacity: .5;
      }

      .album {
        font-size: 4.5vmin;
        font-weight: 100;
        opacity: .5;
      }
    }

    .translucent {
      position: fixed;
      top: -20px;
      left: -20px;
      right: -20px;
      bottom: -20px;
      filter: blur(20px);
      opacity: .1;
      z-index: 10000;
      overflow: hidden;
      background-size: cover;
      background-position: center;
      transform: translateZ(0);
      backface-visibility: hidden;
      perspective: 1000;
      pointer-events: none;
    }

    footer {
      height: 116px;
      display: flex;
      justify-content: space-evenly;
      align-items: center;
      border-top: 1px solid rgba(255, 255, 255, .1);
      font-size: 5vmin;

      .prev, .next {
        font-size: 6vmin;
      }

      .play-pause {
        display: inline-block;
        width: 16vmin;
        height: 16vmin;
        border: 1px solid #fff;
        border-radius: 50%;
        line-height: 16vmin;
        font-size: 7vmin;
      }
    }
  }
</style>
