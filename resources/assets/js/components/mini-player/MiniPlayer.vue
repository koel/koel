<template>
  <div
    ref="root"
    class="mini-player relative isolate flex flex-col justify-end gap-3 h-screen p-4 bg-k-bg text-k-fg overflow-hidden"
  >
    <img :src="cover" alt="" class="absolute inset-0 -z-20 size-full object-cover" />
    <div class="fade reveal absolute inset-0 -z-10" />

    <div class="reveal overflow-hidden">
      <h3 class="font-semibold overflow-hidden whitespace-nowrap text-ellipsis">{{ title }}</h3>
      <p class="text-k-fg-70 text-[0.9rem] overflow-hidden whitespace-nowrap text-ellipsis">{{ subtitle }}</p>
    </div>

    <footer class="reveal -mx-4 -mb-4">
      <div
        v-if="!isRadio"
        :aria-valuenow="progress"
        aria-label="Seek"
        aria-valuemax="100"
        aria-valuemin="0"
        class="seek-bar relative z-10 py-2.5 -my-2.5 cursor-pointer"
        role="slider"
        tabindex="0"
        @click="seek"
      >
        <div class="track h-0.5 bg-k-fg-10 transition-transform duration-200">
          <div
            :style="{ width: `${progress}%` }"
            class="h-full bg-k-highlight transition-[width] duration-200 ease-in-out"
          />
        </div>
      </div>
      <div v-else class="h-0.5 bg-k-fg-10" />

      <div class="flex items-center justify-center gap-8 py-4">
        <FavoriteButton
          v-if="streamable"
          :favorite="streamable.favorite"
          class="control text-base"
          @toggle="toggleFavorite"
        />
        <span v-else class="w-4" />

        <button :disabled="!canSkip" class="control text-2xl" type="button" @click.prevent="playPrev">
          <Icon :icon="faStepBackward" />
          <span class="sr-only">Play previous in queue</span>
        </button>

        <button class="control text-3xl" type="button" @click.prevent="togglePlayback">
          <Icon :icon="playing ? faPause : faPlay" fixed-width />
          <span class="sr-only">{{ playing ? 'Pause' : 'Play or resume' }}</span>
        </button>

        <button :disabled="!canSkip" class="control text-2xl" type="button" @click.prevent="playNext">
          <Icon :icon="faStepForward" />
          <span class="sr-only">Play next in queue</span>
        </button>

        <RepeatModeSwitch :class="isRadio && 'pointer-events-none opacity-30'" class="text-base" />
      </div>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { faPause, faPlay, faStepBackward, faStepForward } from '@fortawesome/free-solid-svg-icons'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { getPlayableProp, requireInjection } from '@/utils/helpers'
import { isRadioStation } from '@/utils/typeGuards'
import { getCoverBackgroundColor } from '@/utils/coverColor'
import { CurrentStreamableKey } from '@/config/symbols'
import { playback } from '@/services/playbackManager'
import { useBranding } from '@/composables/useBranding'
import { usePlaybackToggle } from '@/composables/usePlaybackToggle'
import { playableStore } from '@/stores/playableStore'
import { radioStationStore } from '@/stores/radioStationStore'

import FavoriteButton from '@/components/ui/FavoriteButton.vue'
import RepeatModeSwitch from '@/components/ui/RepeatModeSwitch.vue'

const { cover: defaultCover } = useBranding()

const streamable = requireInjection(CurrentStreamableKey, ref())
const root = ref<HTMLElement>()
const { togglePlayback } = usePlaybackToggle(streamable)
const position = ref(0)
const duration = ref(0)
const fadeColor = ref<string | null>(null)

const isRadio = computed(() => Boolean(streamable.value && isRadioStation(streamable.value)))
const canSkip = computed(() => Boolean(streamable.value) && !isRadio.value)
const playing = computed(() => streamable.value?.playback_state === 'Playing')
const progress = computed(() => (duration.value ? (position.value / duration.value) * 100 : 0))

const cover = computed(() => {
  if (!streamable.value) {
    return defaultCover
  }

  if (isRadioStation(streamable.value)) {
    return streamable.value.logo ?? defaultCover
  }

  return getPlayableProp(streamable.value, 'album_cover', 'episode_image') || defaultCover
})

const title = computed(() => {
  if (!streamable.value) {
    return ''
  }

  return isRadioStation(streamable.value) ? streamable.value.name : streamable.value.title
})

const subtitle = computed(() => {
  if (!streamable.value || isRadioStation(streamable.value)) {
    return ''
  }

  return getPlayableProp(streamable.value, 'artist_name', 'podcast_title')
})

const fadeBackground = computed(() => {
  const color = fadeColor.value ?? 'var(--color-bg)'

  return `linear-gradient(to top, ${color} 0%, ${color} 35%, transparent 75%)`
})

watch(
  cover,
  async url => {
    const color = await getCoverBackgroundColor(url)

    if (url === cover.value) {
      fadeColor.value = color
    }
  },
  { immediate: true },
)

const toggleFavorite = () => {
  if (isRadioStation(streamable.value!)) {
    radioStationStore.toggleFavorite(streamable.value)
  } else {
    playableStore.toggleFavorite(streamable.value!)
  }
}

const playPrev = async () => await playback().playPrev()
const playNext = async () => await playback().playNext()

const seek = (event: MouseEvent) => {
  const media = playback('current')?.media
  const slider = event.currentTarget as HTMLElement

  if (!media?.duration) {
    return
  }

  const { left, width } = slider.getBoundingClientRect()
  playback('current')?.seekTo(((event.clientX - left) / width) * media.duration)
  updateProgress()
}

const updateProgress = () => {
  const media = playback('current')?.media
  const hasKnownDuration = Boolean(media && Number.isFinite(media.duration) && media.duration > 0)

  position.value = hasKnownDuration ? media!.currentTime : 0
  duration.value = hasKnownDuration ? media!.duration : 0
}

let miniPlayerWindow: Window | null = null
let progressInterval: number | undefined

onMounted(() => {
  miniPlayerWindow = root.value!.ownerDocument.defaultView
  progressInterval = miniPlayerWindow?.setInterval(updateProgress, 250)
})

onBeforeUnmount(() => miniPlayerWindow?.clearInterval(progressInterval))
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.fade {
  background-image: v-bind(fadeBackground);
}

.reveal {
  @apply transition-opacity duration-300;

  .mini-player:not(:hover, :focus-within) & {
    @apply opacity-0;
  }
}

.seek-bar:hover .track {
  @apply scale-y-200;
}

.control {
  @apply text-k-fg-70 hover:text-k-fg transition-[color] duration-200 disabled:opacity-30 disabled:pointer-events-none;
}
</style>
