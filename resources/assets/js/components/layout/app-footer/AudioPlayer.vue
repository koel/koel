<template>
  <div class="audio-player w-full h-[4px] relative" :class="{ loading: isLoading }">
    <audio id="audio-player" class="hidden" crossorigin="anonymous" />
    <div
      class="progress-bar absolute top-0 w-full h-full cursor-pointer"
      @click="seek"
      @mousemove="onHover"
      @mouseleave="hoverProgress = 0"
    >
      <div class="progress-buffer absolute top-0 left-0 h-full" :style="{ width: `${bufferProgress}%` }" />
      <div class="progress-hover absolute top-0 left-0 h-full" :style="{ width: `${hoverProgress}%` }" />
      <div class="progress-played absolute top-0 left-0 h-full" :style="{ width: `${progress}%` }" />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { playback } from '@/services/playbackManager'
import { crossfadeService } from '@/services/crossfadeService'

const progress = ref(0)
const bufferProgress = ref(0)
const hoverProgress = ref(0)
const isLoading = ref(false)

const getActiveMedia = (): HTMLMediaElement | null => {
  if (crossfadeService.active && crossfadeService.state) {
    return crossfadeService.state.incomingAudio
  }

  return playback('current')?.media ?? null
}

const updateProgress = () => {
  const media = getActiveMedia()

  if (!media) {
    return
  }

  const { currentTime, duration, buffered, readyState } = media

  if (duration > 0) {
    progress.value = (currentTime / duration) * 100
  } else {
    progress.value = 0
  }

  // Buffer progress
  if (buffered.length > 0 && duration > 0) {
    bufferProgress.value = (buffered.end(buffered.length - 1) / duration) * 100
  } else {
    bufferProgress.value = 0
  }

  // Loading state: has src but not enough data to play
  isLoading.value = !!media.src && readyState < 3 && currentTime === 0
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

const onHover = (e: MouseEvent) => {
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
  hoverProgress.value = ((e.clientX - rect.left) / rect.width) * 100
}

setInterval(updateProgress, 250)
</script>

<style lang="postcss" scoped>
.progress-buffer {
  @apply bg-white/10 transition-[width] duration-200 ease-in-out;
}

.progress-hover {
  @apply bg-white/5 opacity-0 transition-opacity;
}

.progress-bar:hover .progress-hover {
  @apply opacity-100;
}

.progress-played {
  @apply bg-k-fg-10 transition-[width] duration-200 ease-in-out;
}

.audio-player:hover .progress-played {
  @apply bg-k-highlight;
}

.progress-played {
  @apply no-hover:bg-k-highlight;
}

/* Loading animation: diagonal stripes */
.audio-player.loading .progress-buffer {
  animation: progress-stripes 1s linear infinite;
  background-size: 40px 40px;
  background-repeat: repeat-x;
  background-color: rgba(86, 93, 100, 0.25);
  background-image: linear-gradient(
    -45deg,
    rgba(0, 0, 0, 0.15) 25%,
    transparent 25%,
    transparent 50%,
    rgba(0, 0, 0, 0.15) 50%,
    rgba(0, 0, 0, 0.15) 75%,
    transparent 75%,
    transparent
  );
}

@keyframes progress-stripes {
  to {
    background-position: 40px 0;
  }
}

:fullscreen .audio-player {
  @apply z-[4] bg-white/20 rounded-full overflow-hidden;
}

:fullscreen .progress-played {
  @apply bg-white !important;
}
</style>
