<template>
  <div class="audio-player" :class="{ loading: isLoading, dragging: isDragging }">
    <audio id="audio-player" class="hidden" crossorigin="anonymous" />
    <slot :progress />
    <!--
      The hit area is absolutely positioned over the top of the footer,
      extending above and below the visible 4px track for easy clicking.
    -->
    <div
      :aria-valuemax="durationSeconds"
      :aria-valuenow="positionSeconds"
      :aria-valuetext="positionText"
      aria-label="Seek"
      aria-valuemin="0"
      class="hit-area"
      role="slider"
      tabindex="0"
      @keydown.home.prevent="seekToStart"
      @keydown.end.prevent="seekToEnd"
      @pointerdown="onPointerDown"
      @click="onClickSeek"
      @mousemove="onHover"
      @mouseleave="onHoverEnd"
    >
      <div class="track">
        <div class="progress-buffer" :style="{ width: `${bufferProgress}%` }" />
        <div class="progress-hover" :style="{ width: `${hoverProgress}%` }" />
        <div class="progress-played" :style="{ width: `${progress}%` }" />
      </div>
    </div>

    <template v-if="durationSeconds && hoveredTrackRect">
      <ProgressTimeTip
        :track-rect="hoveredTrackRect"
        :percent="progress"
        :seconds="positionSeconds"
        data-testid="playhead-time"
      />
      <ProgressTimeTip
        :track-rect="hoveredTrackRect"
        :percent="hoverProgress"
        :seconds="hoverSeconds"
        data-testid="cursor-time"
      />
      <ProgressTimeTip
        v-if="!nearsTotalTip(progress) && !nearsTotalTip(hoverProgress)"
        :track-rect="hoveredTrackRect"
        :percent="100"
        :seconds="durationSeconds"
        align="end"
        data-testid="total-time"
      />
    </template>
  </div>
</template>

<script lang="ts" setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import { playback } from '@/services/playbackManager'
import { crossfadeService } from '@/services/crossfadeService'
import { secondsToHis } from '@/utils/formatters'

import ProgressTimeTip from '@/components/layout/app-footer/ProgressTimeTip.vue'

const progress = ref(0)
const bufferProgress = ref(0)
const hoverProgress = ref(0)
const isLoading = ref(false)
const isDragging = ref(false)
const positionSeconds = ref(0)
const durationSeconds = ref(0)

const hoverSeconds = computed(() => Math.round((hoverProgress.value / 100) * durationSeconds.value))
const hoveredTrackRect = ref<DOMRect | null>(null)

const TOTAL_TIP_CLEARANCE_PX = 100

const nearsTotalTip = (percent: number) =>
  (hoveredTrackRect.value!.width * (100 - percent)) / 100 < TOTAL_TIP_CLEARANCE_PX

const positionText = computed(() => `${secondsToHis(positionSeconds.value)} of ${secondsToHis(durationSeconds.value)}`)

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

  const hasKnownDuration = Number.isFinite(duration) && duration > 0

  positionSeconds.value = hasKnownDuration ? Math.round(currentTime) : 0
  durationSeconds.value = hasKnownDuration ? Math.round(duration) : 0

  // Buffer progress
  if (buffered.length > 0 && duration > 0) {
    bufferProgress.value = (buffered.end(buffered.length - 1) / duration) * 100
  } else {
    bufferProgress.value = 0
  }

  // Loading state: has src but not enough data to play
  isLoading.value = !!media.src && readyState < 3 && currentTime === 0
}

let trackEl: HTMLElement | null = null

const computeRatio = (clientX: number, track: HTMLElement) => {
  const rect = track.getBoundingClientRect()

  if (rect.width === 0) {
    return 0
  }

  return Math.max(0, Math.min(1, (clientX - rect.left) / rect.width))
}

const seekFromEvent = (e: MouseEvent | PointerEvent) => {
  const service = playback('current')
  const media = getActiveMedia()
  const targetTrack = trackEl ?? (e.currentTarget as HTMLElement)?.querySelector<HTMLElement>('.track')

  if (!service || !media?.duration || !targetTrack) {
    return
  }

  service.seekTo(computeRatio(e.clientX, targetTrack) * media.duration)
}

const seekToStart = () => playback('current')?.seekTo(0)

const seekToEnd = () => {
  const service = playback('current')
  const media = getActiveMedia()

  if (!service || !media?.duration) {
    return
  }

  service.seekTo(media.duration)
}

const onClickSeek = (e: MouseEvent) => {
  if (isDragging.value) {
    return
  }

  seekFromEvent(e)
}

const onPointerDown = (e: PointerEvent) => {
  if (e.button !== 0) {
    return
  }

  trackEl = (e.currentTarget as HTMLElement).querySelector('.track')

  if (!trackEl) {
    return
  }

  e.preventDefault()
  isDragging.value = true
  seekFromEvent(e)

  document.addEventListener('pointermove', onDragMove)
  document.addEventListener('pointerup', onDragEnd)
}

const onDragMove = (e: PointerEvent) => {
  if (!isDragging.value || !trackEl) {
    return
  }

  progress.value = computeRatio(e.clientX, trackEl) * 100
  seekFromEvent(e)
}

const onDragEnd = () => {
  isDragging.value = false
  trackEl = null
  document.removeEventListener('pointermove', onDragMove)
  document.removeEventListener('pointerup', onDragEnd)
}

const onHover = (e: MouseEvent) => {
  if (isDragging.value) {
    return
  }

  const track = (e.currentTarget as HTMLElement).querySelector<HTMLElement>('.track')!
  hoveredTrackRect.value = track.getBoundingClientRect()
  hoverProgress.value = computeRatio(e.clientX, track) * 100
}

const onHoverEnd = () => {
  hoverProgress.value = 0
  hoveredTrackRect.value = null
}

const progressInterval = setInterval(updateProgress, 250)

onBeforeUnmount(() => {
  clearInterval(progressInterval)
  document.removeEventListener('pointermove', onDragMove)
  document.removeEventListener('pointerup', onDragEnd)
})
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.hit-area {
  @apply absolute left-0 right-0 top-0 cursor-pointer;
  z-index: 30;
  /* Extend the clickable area above and below the visible track */
  padding-top: 10px;
  padding-bottom: 10px;
  margin-top: -10px;
}

.track {
  @apply relative w-full;
  height: var(--progress-bar-height);
}

.progress-buffer {
  @apply absolute top-0 left-0 h-full bg-white/10 transition-[width] duration-200 ease-in-out;
}

.progress-hover {
  @apply absolute top-0 left-0 h-full bg-white/5 opacity-0 transition-opacity;
}

.hit-area:hover .progress-hover {
  @apply opacity-100;
}

.progress-played {
  @apply absolute top-0 left-0 h-full bg-k-fg-10 transition-[width] duration-200 ease-in-out;
}

.audio-player:hover .progress-played,
.audio-player.dragging .progress-played {
  @apply bg-k-highlight;
}

.audio-player.dragging .progress-played {
  @apply transition-none;
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
  @apply relative;
}

:fullscreen .track {
  @apply bg-white/20 rounded-full overflow-hidden;
}

:fullscreen .progress-played {
  @apply bg-white!;
}
</style>
