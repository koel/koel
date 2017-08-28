<template>
  <div id="app" :class="{ 'standalone' : inStandAloneMode }">
    <template v-if="authenticated">
      <div class="translucent" v-if="song" :style="{ backgroundImage: 'url('+song.album.cover+')' }">
      </div>
      <div id="main">
        <p class="collapse">
          <i class="fa fa-angle-down"></i>
        </p>
        <template v-if="connected">
          <div class="details" v-if="song">
            <div class="cover" :style="{ backgroundImage: 'url('+song.album.cover+')' }"></div>
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
        </template>
        <div v-else class="loader">
          <p><span>Searching for Koel…</span></p>
          <div class="signal"></div>
        </div>
      </div>
    </template>

    <div class="login-wrapper" v-else>
      <login-form @loggedin="onUserLoggedIn"/>
    </div>
  </div>
</template>

<script>
  import { socket, ls } from '../services'
  import { userStore } from '../stores'
  import loginForm from '../components/auth/login-form.vue'

  export default {
    components: { loginForm },

    data () {
      return {
        authenticated: false,
        song: null,
        lastActiveTime: new Date().getTime(),
        inStandAloneMode: false,
        connected: false
      }
    },

    methods: {
      onUserLoggedIn () {
        this.authenticated = true
        this.init()
      },

      async init() {
        try {
          const user = await userStore.getProfile()
          userStore.init([], user)

          await socket.init()

          socket.listen('song', ({ song }) => {
            this.connected = true
            this.song = song
          }).listen('playback:stopped', () => {
            if (this.song) {
              this.song.playbackState = 'stopped'
            }
          })

          this.getStatus()
        } catch (e) {
          console.log(e)
          this.authenticated = false
        }
      },

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
      },

      /**
       * As iOS will put a web app into standby/sleep mode (and halt all JS execution), 
       * this method will keep track of the last active time and keep the status always fresh.
       */
      heartbeat () {
        const now = new Date().getTime()
        if (now - this.lastActiveTime > 2000) {
          this.getStatus()
        }
        this.lastActiveTime = now
      }
    },

    computed: {
      playing () {
        return this.song && this.song.playbackState === 'playing'
      }
    },

    created () {
      window.setInterval(this.heartbeat, 500)
      this.inStandAloneMode = window.navigator.standalone
    },

    mounted () {
      // The app has just been initialized, check if we can get the user data with an already existing token
      const token = ls.get('jwt-token')
      if (token) {
        this.authenticated = true
        this.init()
      }
    }
  }
</script>

<style lang="scss">
  @import "resources/assets/sass/partials/_vars.scss";
  @import "resources/assets/sass/partials/_mixins.scss";
  @import "resources/assets/sass/partials/_shared.scss";

  #app {
    height: 100%;
    background: $colorMainBgr;

    .login-wrapper {
      display: flex;
      min-height: 100vh;
      flex-direction: column;

      @include vertical-center();
    }

    .translucent {
      position: absolute;
      top: -20px;
      left: -20px;
      right: -20px;
      bottom: -20px;
      filter: blur(20px);
      opacity: .3;
      z-index: 0; 
      overflow: hidden;
      background-size: cover;
      background-position: center;
      transform: translateZ(0);
      backface-visibility: hidden;
      perspective: 1000;
      pointer-events: none;
    }

    .loader {
      display: flex;
      justify-content: center;
      align-items: center;
      flex: 1;
      position: relative;

      p {
        position: absolute;
        height: 100%;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        top: 0;
        left: 0;
        padding-bottom: 40px;
      }

      .signal {
        border: 1px solid $colorOrange;
        border-radius: 50%;
        height: 0;
        opacity: 0;
        width: 50vw;
        animation: pulsate 1.5s ease-out;
        animation-iteration-count: infinite;
        transform: translate(-50%, -50%);
      }

      @keyframes pulsate {
        0% {
          transform:scale(.1);
          opacity: 0.0;
        }
        50% {
          opacity:1;
        }
        100% {
          transform:scale(1.2);
          opacity:0;
        }
      }
    }
  }

  #main {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-align: center;
    z-index: 1;
    position: relative;

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
      justify-content: space-around;

      .info {
        width: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-around;
      }

      .cover {
        margin: 0 auto;
        width: calc(70vw + 4px);
        height: calc(70vw + 4px);
        border-radius: 50%;
        border: 2px solid #fff;
        background-position: center center;
        background-size: cover;
        background-color: #2d2f2f;
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

  #app.standalone {
    padding-top: 20px;

    #main {
      .details {
        .cover {
          width: calc(80vw - 4px);
          height: calc(80vw - 4px);
        }
      }

      .footer {
        height: 20vh;
      }
    }
  }
</style>
