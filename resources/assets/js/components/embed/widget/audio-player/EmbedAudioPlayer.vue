<template>
  <div class="flex gap-2 items-center">
    <PlayButton :playable="currentPlayable" :preview :progress @clicked="playOrPause" />
    <NextButton v-if="playables.length > 1" :playable="nextPlayable" @clicked="playNext" />

    <template v-if="!preview">
      <ProgressBar :playable="currentPlayable" :progress class="ml-2" @seek="seek" />

      <span
        v-show="timeRemainingLabel"
        class="min-w-16 text-k-text-secondary flex items-center justify-end font-mono"
      >
        {{ timeRemainingLabel }}
      </span>
    </template>

    <audio ref="audio" class="hidden" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, toRefs } from 'vue'
import { throttle } from 'lodash'
import { secondsToHis } from '@/utils/formatters'

import ProgressBar from '@/components/embed/widget/audio-player/EmbedAudioPlayerProgressBar.vue'
import PlayButton from '@/components/embed/widget/audio-player/EmbedAudioPlayerPlayButton.vue'
import NextButton from '@/components/embed/widget/audio-player/EmbedAudioPlayerNextButton.vue'

const props = defineProps<{ playables: Playable[], preview: boolean }>()

const { playables, preview } = toRefs(props)

const audio = ref<HTMLAudioElement>()

const timeRemainingLabel = ref('')
const maxDuration = ref(0)
const progress = ref(0)

const currentPlayable = computed(() => playables.value!.find(playable => {
  return playable.playback_state === 'Playing' || playable.playback_state === 'Paused'
}))

const seek = (percentage: number) => {
  if (!maxDuration.value) {
    return
  }

  audio.value!.currentTime = percentage / 100 * maxDuration.value
}

const nextPlayable = computed(() => {
  if (playables.value.length === 0) {
    return null
  }

  if (!currentPlayable.value) {
    return playables.value[0]
  }

  const nextIndex = playables.value.indexOf(currentPlayable.value) + 1

  return playables.value[nextIndex] || null
})

const play = (playable: Playable) => {
  if (currentPlayable.value) {
    currentPlayable.value.playback_state = 'Stopped'
  }

  audio.value!.src = playable.embed_stream_url!
  playable.playback_state = 'Playing'
  audio.value!.play()
}

const pause = () => {
  if (currentPlayable.value) {
    currentPlayable.value.playback_state = 'Paused'
    audio.value!.pause()
  }
}

const resume = () => {
  if (currentPlayable.value) {
    currentPlayable.value.playback_state = 'Playing'
    audio.value!.play()
  }
}

const stop = () => {
  if (currentPlayable.value) {
    currentPlayable.value.playback_state = 'Stopped'
    audio.value!.src = ''
  }
}

const playOrPause = () => {
  if (currentPlayable.value) {
    currentPlayable.value.playback_state === 'Playing' ? pause() : resume()
  } else {
    if (playables.value!.length === 0) {
      return
    }

    play(playables.value[0])
  }
}

const playNext = () => nextPlayable.value ? play(nextPlayable.value) : stop()

const setupAudioEvents = (audio: HTMLAudioElement) => {
  audio.addEventListener('loadedmetadata', () => {
    maxDuration.value = preview.value ? Math.min(audio.duration, 30) : audio.duration
    audio.volume = 0.75
  })

  audio.addEventListener('ended', () => playNext())

  audio.addEventListener('timeupdate', throttle(() => {
    progress.value = audio.currentTime / maxDuration.value! * 100

    const timeRemaining = secondsToHis(maxDuration.value - audio.currentTime)
    timeRemainingLabel.value = timeRemaining === 'NaN:NaN' ? '--:--' : `-${timeRemaining}`

    if (preview.value) {
      if (audio.currentTime + 3 >= maxDuration.value) {
        // Gradually decrease the volume once we're approaching the end of the sample.
        audio.volume = Math.max(0, audio.volume - 0.07)
      }

      // Play the next track once we've reached the end of the sample.
      if (audio.currentTime >= maxDuration.value) {
        audio.pause()
        audio.currentTime = 0
        audio.dispatchEvent(new Event('ended'))
      }
    }
  }, 100))
}

onMounted(() => setupAudioEvents(audio.value!))

defineExpose({
  play,
  pause,
  resume,
  stop,
})
</script>
