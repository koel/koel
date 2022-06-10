<template>
  <section id="youtubeWrapper">
    <screen-header>{{ title }}</screen-header>

    <div id="player">
      <ScreenEmptyState data-testid="youtube-placeholder">
        <template v-slot:icon>
          <i class="fa fa-youtube-play"></i>
        </template>
        YouTube videos will be played here.
        <span class="d-block instruction">Start a video playback from the right sidebar.</span>
      </ScreenEmptyState>
    </div>
  </section>
</template>

<script lang="ts" setup>
import createYouTubePlayer from 'youtube-player'
import { defineAsyncComponent, ref } from 'vue'
import type { YouTubePlayer } from 'youtube-player/dist/types'
import { eventBus, use } from '@/utils'
import { playbackService } from '@/services'

let player: YouTubePlayer | null = null

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const ScreenEmptyState = defineAsyncComponent(() => import('@/components/ui/ScreenEmptyState.vue'))

const title = ref('YouTube Video')

const getPlayer = () => {
  if (!player) {
    player = createYouTubePlayer('player', {
      width: '100%',
      height: '100%'
    })

    // Pause song playback when video is played
    player.on('stateChange', ({ data }) => data === 1 && playbackService.pause())
  }

  return player
}

eventBus.on({
  PLAY_YOUTUBE_VIDEO (payload: { id: string, title: string }) {
    title.value = payload.title

    use(getPlayer(), player => {
      player.loadVideoById(payload.id)
      player.playVideo()
    })
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
