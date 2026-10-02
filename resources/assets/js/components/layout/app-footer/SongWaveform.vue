<template>
  <div v-if="waveform.length" ref="root" class="song-waveform" data-testid="song-waveform">
    <template v-if="maskImage">
      <div class="unplayed" :style="{ maskImage }" />
      <div
        class="played"
        data-testid="song-waveform-played"
        :style="{ maskImage, clipPath: `inset(0 ${100 - progress}% 0 0)` }"
      />
      <div class="playhead" :style="{ left: `${progress}%` }" />
    </template>
  </div>
</template>

<script lang="ts" setup>
import { useElementSize } from '@vueuse/core'
import { computed, ref, watch } from 'vue'
import { preferenceStore } from '@/stores/preferenceStore'
import { waveformService } from '@/services/waveformService'
import { useKoelPlus } from '@/composables/useKoelPlus'

const props = defineProps<{ song: Song; progress: number }>()

const PIXELS_PER_BAR = 5

const { isPlus } = useKoelPlus()

const root = ref<HTMLElement>()
const waveform = ref<number[]>([])

const { width } = useElementSize(root)
const barCount = computed(() => Math.round(width.value / PIXELS_PER_BAR))

const maskImage = computed(() => {
  if (!waveform.value.length || !barCount.value) {
    return ''
  }

  const svg = waveformService.renderBarsAsSvg(waveform.value, barCount.value)

  return `url("data:image/svg+xml,${encodeURIComponent(svg)}")`
})

watch(
  [() => props.song, () => preferenceStore.show_waveform],
  async ([song, showWaveform]) => {
    waveform.value = []

    if (!isPlus.value || !showWaveform || song.loudness == null) {
      return
    }

    const fetchedWaveform = await waveformService.fetchWaveform(song).catch(() => [])

    if (props.song === song) {
      waveform.value = fetchedWaveform
    }
  },
  { immediate: true },
)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.song-waveform {
  @apply pointer-events-none;
  mask-image:
    linear-gradient(to right, transparent, #000 8%, #000 92%, transparent),
    linear-gradient(to bottom, transparent, #000 10%, #000 90%, transparent);
  mask-composite: intersect;
}

.unplayed,
.played {
  @apply absolute inset-0;
  mask-size: 100% 92%;
  mask-position: center;
  mask-repeat: no-repeat;
}

.unplayed {
  @apply bg-k-fg-10;
}

.played {
  @apply bg-k-highlight opacity-40 transition-[clip-path] duration-200 ease-in-out;
}

.playhead {
  @apply absolute inset-y-0 w-0.5 -translate-x-1/2 bg-k-highlight transition-[left] duration-200 ease-in-out;
}

:fullscreen .song-waveform {
  @apply hidden;
}
</style>
