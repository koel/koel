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

<script lang="ts" setup>
import { defineAsyncComponent, ref } from 'vue'
import { YouTubePlayer } from 'youtube-player/dist/types'
import { eventBus } from '@/utils'
import { playback } from '@/services'
import createYouTubePlayer from 'youtube-player'

let player: YouTubePlayer|null = null

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/screen-header.vue'))
const ScreenPlaceholder = defineAsyncComponent(() => import('@/components/ui/screen-placeholder.vue'))

const title = ref('YouTube Video')

/**
 * Initialize the YouTube player. This should only be called once.
 */
const maybeInitPlayer = () => {
  if (!player) {
    player = createYouTubePlayer('player', {
      width: '100%',
      height: '100%'
    })

    // Pause song playback when video is played
    player.on('stateChange', ({ data }) => data === 1 && playback.pause())
  }
}

eventBus.on({
  'PLAY_YOUTUBE_VIDEO': (payload: { id: string, title: string }) => {
    title.value = payload.title
    maybeInitPlayer()
    player!.loadVideoById(payload.id)
    player!.playVideo()
  },

  /**
   * Stop video playback when a song is played/resumed.
   */
  'SONG_STARTED': () => player && player.pauseVideo()
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
