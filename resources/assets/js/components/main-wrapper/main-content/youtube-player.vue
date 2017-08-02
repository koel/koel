<template>
  <section id="youtubeWrapper">
    <h1 class="heading"><span>YouTube 视频</span></h1>
    <div id="player">
      <p class="none">YouTube 视频会在这里播放.<br/>
	您可以从右侧栏开始播放视频.<br>
	需要注意的是，视频的音量，进度等都是从视频内部控制的,而不是通过Koel.</p>
    </div>
  </section>
</template>

<script>
import { event } from '../../../utils'
import { playback } from '../../../services'
import YouTubePlayer from 'youtube-player'

let player

export default {
  name: 'main-wrapper--main-content--youtube-player',

  methods: {
    /**
     * Initialize the YouTube player. This should only be called once.
     */
    initPlayer () {
      if (!player) {
        player = YouTubePlayer('player', {
          width: '100%',
          height: '100%'
        })

        player.on('stateChange', event => {
          // Pause song playback when video is played
          event.data === 1 && playback.pause()
        })
      }
    }
  },

  created () {
    event.on({
      'youtube:play': id => {
        this.initPlayer()
        player.loadVideoById(id)
        player.playVideo()
      },

      /**
       * Stop video playback when a song is played/resumed.
       */
      'song:played': () => player && player.pauseVideo()
    })
  }
}
</script>

<style lang="sass" scoped>
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

.none {
  color: $color2ndText;
  padding: 16px 24px;
}
</style>
