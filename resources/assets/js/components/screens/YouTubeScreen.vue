<template>
  <section id="youtubeWrapper">
    <ScreenHeader layout="collapsed">{{ title }}</ScreenHeader>

    <div id="player">
      <ScreenEmptyState data-testid="youtube-placeholder">
        <template v-slot:icon>
          <icon :icon="faYoutube"/>
        </template>
        YouTube videos will be played here.
        <span class="d-block instruction">Start a video playback from the right sidebar.</span>
      </ScreenEmptyState>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { faYoutube } from '@fortawesome/free-brands-svg-icons'
import createYouTubePlayer from 'youtube-player'
import { ref } from 'vue'
import type { YouTubePlayer } from 'youtube-player/dist/types'
import { eventBus, use } from '@/utils'
import { playbackService } from '@/services'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'

let player: YouTubePlayer | null = null
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
  SONG_STARTED: () => player && player.pauseVideo()
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
