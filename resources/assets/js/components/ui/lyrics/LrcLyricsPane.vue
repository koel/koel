<template>
  <article
    ref="lyricsContainer"
    v-koel-overflow-fade
    class="overflow-y-auto space-y-2"
  >
    <LrcLyricsLine
      v-for="(line, index) in lyrics"
      :key="index"
      :is-active="index === currentLineIndex"
      :line
      :style="{ opacity: Math.max(0.1, 1 - Math.abs(index - currentLineIndex) / 4) }"
      class="hover:!opacity-100"
      @click="seekToLine(line)"
    />
  </article>
</template>

<script lang="ts" setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { playback } from '@/services/playbackManager'
import type { BasePlaybackService } from '@/services/BasePlaybackService'

import LrcLyricsLine from '@/components/ui/lyrics/LrcLyricsLine.vue'

const props = defineProps<{
  lyrics: LrcLine[]
  fontSize: string
}>()

let currentPlayback: BasePlaybackService | null

const lyricsContainer = ref<HTMLDivElement | null>(null)
const currentLineIndex = ref(-1)

const scrollToCurrentLine = async () => {
  if (!lyricsContainer.value || currentLineIndex.value < 0) {
    return
  }

  await nextTick()

  const container = lyricsContainer.value
  const lines = container.querySelectorAll<HTMLParagraphElement>('p')
  const currentLine = lines[currentLineIndex.value]

  if (currentLine) {
    const containerHeight = container.clientHeight
    const lineTop = currentLine.offsetTop
    const lineHeight = currentLine.clientHeight
    const scrollTop = lineTop - containerHeight / 2 + lineHeight / 2

    // Prevent the scroll bar from "flashing" when scrolling to the current line
    // by temporarily adding a class to the container, which hides the scrollbar.
    // After a short delay (approximately enough for scrollTo() to finish), remove
    // the class to restore the scrollbar's default visibility.
    container.classList.add('scrolling-to-view')
    setTimeout(() => container.classList.remove('scrolling-to-view'), 300)

    container.scrollTo({
      top: scrollTop,
      behavior: 'smooth',
    })
  }
}

const updateCurrentLine = () => {
  const currentTime = currentPlayback?.player.media.currentTime || 0
  let newIndex = -1

  for (let i = props.lyrics.length - 1; i >= 0; i--) {
    if (currentTime >= props.lyrics[i].time) {
      newIndex = i
      break
    }
  }

  if (newIndex !== currentLineIndex.value) {
    currentLineIndex.value = newIndex
    scrollToCurrentLine()
  }
}

// Consolidated time update management
let timeUpdateInterval: number | null = null

const stopTimeUpdates = () => {
  if (timeUpdateInterval !== null) {
    clearInterval(timeUpdateInterval)
    timeUpdateInterval = null
  }
}

const startTimeUpdates = () => {
  stopTimeUpdates()

  if (props.lyrics.length > 0 && typeof window !== 'undefined') {
    timeUpdateInterval = window.setInterval(updateCurrentLine, 100)
  }
}

watch(() => props.lyrics, () => {
  currentLineIndex.value = -1

  lyricsContainer.value?.scrollTo({
    top: 0,
    behavior: 'smooth',
  })

  startTimeUpdates()
}, { immediate: true, deep: true })

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', stopTimeUpdates)
  }

  currentPlayback = playback('current')
})

const seekToLine = (line: LrcLine) => currentPlayback?.seekTo(line.time)

onBeforeUnmount(() => {
  stopTimeUpdates()
  if (typeof window !== 'undefined') {
    window.removeEventListener('beforeunload', stopTimeUpdates)
  }
})
</script>

<style lang="postcss" scoped>
article {
  font-size: v-bind(fontSize);

  &.scrolling-to-view {
    scrollbar-width: none; /* Firefox */

    &::-webkit-scrollbar {
      display: none;
    }
  }
}
</style>
