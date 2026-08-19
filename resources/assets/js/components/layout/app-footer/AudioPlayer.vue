<template>
  <div class="audio-player" :class="{ loading: isLoading, dragging: isDragging }">
    <audio id="audio-player" class="hidden" crossorigin="anonymous" />
    <!--
      The hit area is absolutely positioned over the top of the footer,
      extending above and below the visible 4px track for easy clicking.
    -->
    <div
      class="hit-area"
      @pointerdown="onPointerDown"
      @click="onClickSeek"
      @mousemove="onHover"
      @mouseleave="hoverProgress = 0"
    >
      <div class="track">
        <div class="progress-buffer" :style="{ width: `${bufferProgress}%` }" />
        <div class="progress-hover" :style="{ width: `${hoverProgress}%` }" />
        <div class="progress-played" :style="{ width: `${progress}%` }" />
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { onBeforeUnmount, ref } from 'vue'
import { playback } from '@/services/playbackManager'

const progress = ref(0)
const bufferProgress = ref(0)
const hoverProgress = ref(0)
const isLoading = ref(false)
const isDragging = ref(false)

const getActivePlayback = () => {
  const service = playback('current')

  return service
    ? {
        media: service.media,
        position: service.position,
        duration: service.duration,
        bufferedThrough: service.bufferedThrough,
      }
    : null
}

const updateProgress = () => {
  const activePlayback = getActivePlayback()

  if (!activePlayback) {
    return
  }

  const { media, position, duration, bufferedThrough } = activePlayback

  if (duration > 0) {
    progress.value = Math.min(100, (position / duration) * 100)
  } else {
    progress.value = 0
  }

  if (bufferedThrough > 0 && duration > 0) {
    bufferProgress.value = Math.min(100, (bufferedThrough / duration) * 100)
  } else {
    bufferProgress.value = 0
  }

  isLoading.value = !!media.src && media.readyState < 3
}

let trackEl: HTMLElement | null = null
let suppressNextClick = false
let suppressClickResetTimer: number | null = null

const clearSuppressClickReset = () => {
  if (suppressClickResetTimer === null) {
    return
  }

  window.clearTimeout(suppressClickResetTimer)
  suppressClickResetTimer = null
}

const scheduleSuppressClickReset = () => {
  clearSuppressClickReset()
  suppressClickResetTimer = window.setTimeout(() => {
    suppressNextClick = false
    suppressClickResetTimer = null
  })
}

const computeRatio = (clientX: number, track: HTMLElement) => {
  const rect = track.getBoundingClientRect()

  if (rect.width === 0) {
    return 0
  }

  return Math.max(0, Math.min(1, (clientX - rect.left) / rect.width))
}

const seekFromEvent = (e: MouseEvent | PointerEvent) => {
  const service = playback('current')
  const targetTrack = trackEl ?? (e.currentTarget as HTMLElement)?.querySelector<HTMLElement>('.track')

  if (!service?.duration || !targetTrack) {
    return
  }

  service.seekTo(computeRatio(e.clientX, targetTrack) * service.duration)
}

const onClickSeek = (e: MouseEvent) => {
  if (isDragging.value || suppressNextClick) {
    clearSuppressClickReset()
    suppressNextClick = false
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
  clearSuppressClickReset()
  suppressNextClick = false
  progress.value = computeRatio(e.clientX, trackEl) * 100

  document.addEventListener('pointermove', onDragMove)
  document.addEventListener('pointerup', onDragEnd)
  document.addEventListener('pointercancel', onDragCancel)
}

const onDragMove = (e: PointerEvent) => {
  if (!isDragging.value || !trackEl) {
    return
  }

  progress.value = computeRatio(e.clientX, trackEl) * 100
}

const stopDragging = () => {
  isDragging.value = false
  trackEl = null
  document.removeEventListener('pointermove', onDragMove)
  document.removeEventListener('pointerup', onDragEnd)
  document.removeEventListener('pointercancel', onDragCancel)
}

const onDragEnd = (e: PointerEvent) => {
  seekFromEvent(e)
  suppressNextClick = true
  scheduleSuppressClickReset()
  stopDragging()
}

const onDragCancel = () => stopDragging()

const onHover = (e: MouseEvent) => {
  if (isDragging.value) {
    return
  }

  const track = (e.currentTarget as HTMLElement).querySelector<HTMLElement>('.track')!
  hoverProgress.value = computeRatio(e.clientX, track) * 100
}

const progressInterval = setInterval(updateProgress, 250)

onBeforeUnmount(() => {
  clearInterval(progressInterval)
  clearSuppressClickReset()
  document.removeEventListener('pointermove', onDragMove)
  document.removeEventListener('pointerup', onDragEnd)
  document.removeEventListener('pointercancel', onDragCancel)
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
