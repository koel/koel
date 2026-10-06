<template>
  <div v-if="waveform.length" class="song-waveform" data-testid="song-waveform">
    <canvas ref="canvas" class="block w-full h-full" />
  </div>
</template>

<script lang="ts" setup>
import { usePreferredReducedMotion } from '@vueuse/core'
import { onBeforeUnmount, ref, watch } from 'vue'
import { preferenceStore } from '@/stores/preferenceStore'
import { waveformService } from '@/services/waveformService'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { getCoverColor } from '@/utils/coverColor'
import { countWaveformPoints, drawWaveform, GLOW_ROOM, shapeLevels } from '@/utils/waveformCanvas'

const props = defineProps<{ song: Song; progress: number }>()

const SINK_DURATION = 180
const RISE_STAGGER = 320
const RISE_DURATION = 220

const glowRoom = `${GLOW_ROOM}px`

const { isPlus } = useKoelPlus()
const reducedMotion = usePreferredReducedMotion()

const canvas = ref<HTMLCanvasElement>()
const waveform = ref<number[]>([])

let barLevels: number[] = []
let barLevelsKey = ''
let shownProgress = 0
let pausedness = 0
let sinkStartedAt: number | null = null
let riseStartedAt: number | null = null
let colors = { accent: '', line: '' }
let coverColor: string | null = null
let colorsReadAt = 0
let lastFrameAt = 0
let frameId = 0

const isPlaying = () => props.song.playback_state === 'Playing'
const easeTowards = (current: number, target: number, rate: number, seconds: number) =>
  current + (target - current) * Math.min(1, seconds * rate)

/**
 * The progress prop only updates a few times a second. While playing, move forward on our own by the
 * elapsed time and only nudge towards the prop, so the line fills smoothly; a seek still jumps quickly.
 */
const advanceProgress = (target: number, seconds: number) => {
  const drift = target - shownProgress

  if (Math.abs(drift) > 0.5) {
    return target
  }

  if (!isPlaying() || !props.song.length) {
    return easeTowards(shownProgress, target, 14, seconds)
  }

  const advanced = Math.min(1, shownProgress + seconds / props.song.length)

  return Math.abs(target - advanced) > 0.02
    ? easeTowards(advanced, target, 14, seconds)
    : easeTowards(advanced, target, 1.5, seconds)
}

const levelsForWidth = (width: number) => {
  const count = countWaveformPoints(width)
  const key = `${count}:${waveform.value.length}:${waveform.value[0]}`

  if (key !== barLevelsKey) {
    barLevels = waveformService.toBarLevels(waveform.value, count)
    barLevelsKey = key
  }

  return barLevels
}

const growthAt = (now: number, count: number) => (bar: number) => {
  if (sinkStartedAt !== null) {
    return Math.max(0, 1 - (now - sinkStartedAt) / SINK_DURATION)
  }

  if (riseStartedAt === null) {
    return 1
  }

  const delay = (bar / count) * RISE_STAGGER
  const t = Math.min(1, Math.max(0, (now - riseStartedAt - delay) / RISE_DURATION))

  return 1 - Math.pow(1 - t, 3)
}

const refreshColors = (now: number) => {
  if (!canvas.value || (now - colorsReadAt < 1000 && colors.accent)) {
    return
  }

  const style = getComputedStyle(canvas.value)
  colors = {
    accent: coverColor ?? style.getPropertyValue('--color-highlight').trim(),
    line: coverColor ?? style.getPropertyValue('--color-fg').trim(),
  }
  colorsReadAt = now
}

const render = (now: number) => {
  if (!canvas.value) {
    return
  }

  const animated = reducedMotion.value !== 'reduce'
  const seconds = lastFrameAt ? Math.min(0.1, (now - lastFrameAt) / 1000) : 0
  lastFrameAt = now

  const target = props.progress / 100

  if (animated) {
    shownProgress = advanceProgress(target, seconds)
    pausedness = easeTowards(pausedness, isPlaying() ? 0 : 1, 6, seconds)
  } else {
    shownProgress = target
    pausedness = isPlaying() ? 0 : 1
  }

  refreshColors(now)

  const levels = levelsForWidth(canvas.value.clientWidth)

  drawWaveform(canvas.value, {
    levels: shapeLevels({
      levels,
      growth: animated ? growthAt(now, levels.length) : () => 1,
    }),
    progress: shownProgress,
    playedOpacity: 0.8 * (1 - pausedness * 0.55),
    colors,
  })
}

const loop = (now: number) => {
  render(now)
  frameId = requestAnimationFrame(loop)
}

const stopLoop = () => {
  cancelAnimationFrame(frameId)
  frameId = 0
}

watch(canvas, element => {
  stopLoop()

  if (element) {
    frameId = requestAnimationFrame(loop)
  }
})

watch(
  [() => props.song, () => preferenceStore.show_waveform],
  async ([song, showWaveform]) => {
    if (!isPlus.value || !showWaveform || song.loudness == null) {
      waveform.value = []
      return
    }

    sinkStartedAt = waveform.value.length ? performance.now() : null
    riseStartedAt = null

    const fetchedWaveform = await waveformService.fetchWaveform(song).catch(() => [])

    if (props.song !== song || !preferenceStore.show_waveform) {
      return
    }

    waveform.value = fetchedWaveform
    shownProgress = props.progress / 100
    sinkStartedAt = null
    riseStartedAt = performance.now()
  },
  { immediate: true },
)

watch(
  () => props.song.album_cover,
  async cover => {
    coverColor = null
    colorsReadAt = 0

    if (!cover) {
      return
    }

    const color = await getCoverColor(cover)

    if (props.song.album_cover === cover) {
      coverColor = color
      colorsReadAt = 0
    }
  },
  { immediate: true },
)

onBeforeUnmount(stopLoop)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.song-waveform {
  @apply absolute inset-x-0 bottom-0 pointer-events-none;
  height: calc(80% + v-bind(glowRoom));
}

:fullscreen .song-waveform {
  @apply hidden;
}
</style>
