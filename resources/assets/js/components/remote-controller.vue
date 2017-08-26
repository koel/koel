<template>
  <div id="remoteController">
    <p class="collapse">
      <i class="fa fa-angle-down"></i>
    </p>
    <div class="details" v-if="song">
      <div class="translucent" :style="{ backgroundImage: 'url('+song.album.cover+')' }"></div>
      <div class="cover">
        <img :src="song.album.cover" alt="song.album.cover">
      </div>
      <div class="info">
        <div class="wrap">
          <p class="title text">{{ song.title }}</p>
          <p class="artist text">{{ song.artist.name }}</p>
          <p class="album text">{{ song.album.name }}</p>
        </div>
      </div>
    </div>
    <footer>
      <a class="favorite" @click.prevent="toggleFavorite">
        <i class="fa fa-heart yep" v-if="song && song.liked"></i>
        <i class="fa fa-heart-o" v-else></i>
      </a>
      <a class="prev" @click="playPrev">
        <i class="fa fa-step-backward"></i>
      </a>
      <a class="play-pause" @click.prevent="togglePlayback">
        <i class="fa fa-pause" v-if="playing"></i>
        <i class="fa fa-play" v-else></i>
      </a>
      <a class="next" @click="playNext">
        <i class="fa fa-step-forward"></i>
      </a>
      <a class="volume">
        <i class="fa fa-volume-up"></i>
      </a>
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
      toggleFavorite () {
        if (!this.song) {
          return;
        }

        this.song.liked = !this.song.liked
        socket.broadcast('favorite:toggle')
      },

      togglePlayback () {
        if (this.song) {
          this.song.playbackState = this.song.playbackState === 'playing' ? 'paused' : 'playing'
        }

        socket.broadcast('playback:toggle')
      },

      playNext () {
        socket.broadcast('playback:next')
      },

      playPrev () {
        socket.broadcast('playback:prev')
      },

      getStatus() {
        socket.broadcast('song:getcurrent')
      }
    },

    computed: {
      playing () {
        return this.song && this.song.playbackState === 'playing'
      }
    },

    mounted () {
      socket.listen('song:played', ({ song }) => {
        this.song = song
      }).listen('song:current', ({ song }) => {
        console.log('receiving')
        this.song = song
      })

      this.getStatus()
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
    height: 100%;
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
        justify-content: space-around;
      }

      .cover {
        margin: 0 auto;
        width: calc(70vw);
        height: calc(70vw);
        border-radius: 50%;
        border: 2px solid #fff;
        overflow: hidden;
        
        img {
          width: calc(70vw);
          height: auto;
        }
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
        margin: 0 auto 10px;
      }

      .artist {
        font-size: 5vmin;
        margin: 0 auto 6px;
        font-weight: 100;
        opacity: .5;
      }

      .album {
        font-size: 4vmin;
        font-weight: 100;
        opacity: .5;
      }
    }

    .translucent {
      position: absolute;
      top: -20px;
      left: -20px;
      right: -20px;
      bottom: -20px;
      filter: blur(20px);
      opacity: .3;
      z-index: -1;
      overflow: hidden;
      background-size: cover;
      background-position: center;
      transform: translateZ(0);
      backface-visibility: hidden;
      perspective: 1000;
      pointer-events: none;
    }

    footer {
      height: 18vh;
      display: flex;
      justify-content: space-around;
      align-items: center;
      border-top: 1px solid rgba(255, 255, 255, .1);
      font-size: 5vmin;

      a {
        color: #fff;

        &:active {
          opacity: .8;
        }
      }

      .favorite {
        .yep {
          color: #bf2043;
        }
      }

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

        &.fa-play {
          margin-left: 4px;
        }
      }
    }
  }
</style>
