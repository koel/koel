<template>
  <div class="audio-player w-full h-[4px] relative">
    <audio id="audio-player" class="hidden" crossorigin="anonymous" />
    <div class="progress-bar absolute top-0 w-full h-full bg-transparent cursor-pointer" @click="seek">
      <div class="progress-played h-full transition-all duration-300 ease-in-out" :style="{ width: `${progress}%` }" />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { playback } from '@/services/playbackManager'

const progress = ref(0)

const updateProgress = () => {
  const service = playback('current')

  if (!service?.media) {
    return
  }

  const { currentTime, duration } = service.media

  if (duration > 0) {
    progress.value = (currentTime / duration) * 100
  }
}

const seek = (e: MouseEvent) => {
  const service = playback('current')

  if (!service?.media?.duration) {
    return
  }

  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
  const position = ((e.clientX - rect.left) / rect.width) * service.media.duration
  service.seekTo(position)
}

setInterval(updateProgress, 250)
</script>

<style lang="postcss" scoped>
.progress-played {
  @apply bg-k-fg-10;
}

.audio-player:hover .progress-played {
  @apply bg-k-highlight;
}

.progress-played {
  @apply no-hover:bg-k-highlight;
}

:fullscreen .audio-player {
  @apply z-[4] bg-white/20 rounded-full overflow-hidden;
}

:fullscreen .progress-played {
  @apply bg-white !important;
}
</style>
