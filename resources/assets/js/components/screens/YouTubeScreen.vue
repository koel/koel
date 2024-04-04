<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">{{ title }}</ScreenHeader>
    </template>

    <ScreenEmptyState v-if="!showingVideo" data-testid="youtube-placeholder">
      <template #icon>
        <Icon :icon="faYoutube" />
      </template>
      YouTube videos will be played here.
      <span class="secondary">Start a video playback from the right sidebar.</span>
    </ScreenEmptyState>

    <div id="player" />
  </ScreenBase>
</template>

<script lang="ts" setup>
import { unescape } from 'lodash'
import { faYoutube } from '@fortawesome/free-brands-svg-icons'
import createYouTubePlayer from 'youtube-player'
import { ref, watch } from 'vue'
import type { YouTubePlayer } from 'youtube-player/dist/types'
import { eventBus, requireInjection, use } from '@/utils'
import { playbackService } from '@/services'
import { CurrentSongKey } from '@/symbols'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

let player: YouTubePlayer
const title = ref('YouTube Video')
const showingVideo = ref(false)

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
  showingVideo.value = true
  title.value = unescape(payload.title)

  use(getPlayer(), player => {
    player.loadVideoById(payload.id)
    player.playVideo()
  })
})
</script>

<style lang="postcss" scoped>
:deep(iframe#player) {
  /* this is the iframe created by the YouTubePlayer plugin, not the div element! */
  @apply -m-6 w-auto h-auto flex-1 flex flex-col;
}
</style>
