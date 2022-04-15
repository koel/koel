<template>
  <section id="youtubeWrapper">
    <screen-header>{{ title }}</screen-header>

    <div id="player">
      <screen-placeholder data-testid="youtube-placeholder">
        <template v-slot:icon>
          <i class="fa fa-youtube-play"></i>
        </template>
        YouTube videos will be played here.
        <span class="d-block instruction">
          Start a video playback from the right sidebar.
        </span>
      </screen-placeholder>
    </div>
  </section>
</template>

<script lang="ts">
import Vue from 'vue'
import { YouTubePlayer } from 'youtube-player/dist/types'
import { eventBus } from '@/utils'
import { playback } from '@/services'
import createYouTubePlayer from 'youtube-player'

let player: YouTubePlayer

export default Vue.extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue'),
    ScreenPlaceholder: () => import('@/components/ui/screen-placeholder.vue')
  },

  data: () => ({
    title: 'YouTube Video'
  }),

  methods: {
    /**
     * Initialize the YouTube player. This should only be called once.
     */
    initPlayer (): void {
      if (!player) {
        player = createYouTubePlayer('player', {
          width: '100%',
          height: '100%'
        })

        // Pause song playback when video is played
        player.on('stateChange', (event: any): void => {
          if (event.data === 1) {
            playback.pause()
          }
        })
      }
    }
  },

  created (): void {
    eventBus.on({
      'PLAY_YOUTUBE_VIDEO': ({ id, title }: { id: string, title: string }): void => {
        this.title = title
        this.initPlayer()
        player.loadVideoById(id)
        player.playVideo()
      },

      /**
       * Stop video playback when a song is played/resumed.
       */
      'SONG_STARTED': (): void => {
        if (player) {
          player.pauseVideo()
        }
      }
    })
  }
})
</script>

<style lang="scss" scoped>
#player {
  height: 100%;

  .instruction {
    font-size: 1.5rem;
  }
}
</style>
