<template>
  <section id="youtubeWrapper">
    <ScreenHeader layout="collapsed">{{ title }}</ScreenHeader>

    <div id="player">
      <ScreenEmptyState data-testid="youtube-placeholder">
        <template #icon>
          <Icon :icon="faYoutube" />
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
import { ref, watch } from 'vue'
import type { YouTubePlayer } from 'youtube-player/dist/types'
import { eventBus, requireInjection, use } from '@/utils'
import { playbackService } from '@/services'
import { CurrentSongKey } from '@/symbols'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'

let player: YouTubePlayer
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

const currentSong = requireInjection(CurrentSongKey)

/**
 * Pause video playback when a song is played/resumed.
 */
watch(() => currentSong.value?.playback_state, state => state === 'Playing' && player?.pauseVideo())

eventBus.on('PLAY_YOUTUBE_VIDEO', payload => {
  title.value = payload.title

  use(getPlayer(), player => {
    player.loadVideoById(payload.id)
    player.playVideo()
  })
})
</script>

<style lang="postcss" scoped>
:deep(#player) {
  height: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;

  .instruction {
    font-size: 1.5rem;
  }
}
</style>
