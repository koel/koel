<template>
  <div ref="lyricsContainer" class="lyrics-synced overflow-y-auto max-h-[70vh]">
    <SyncLyricsLine
      v-for="(line, index) in parsedLyrics"
      :key="index"
      :line="line"
      :is-active="index === currentLineIndex"
    />
  </div>
</template>

<script lang="ts" setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { playback } from '@/services/playbackManager'
import SyncLyricsLine from '@/components/ui/SyncLyricsLine.vue'

interface LyricLine {
  time: number
  text: string
}

interface Props {
  parsedLyrics: LyricLine[]
  fontSize: string
}

const props = defineProps<Props>()

const lyricsContainer = ref<HTMLDivElement | null>(null)
const currentLineIndex = ref(-1)

const scrollToCurrentLine = async () => {
  if (!lyricsContainer.value || currentLineIndex.value < 0) {
    return
  }

  await nextTick()

  const container = lyricsContainer.value
  const lines = container.querySelectorAll('.lyrics-line')
  const currentLine = lines[currentLineIndex.value] as HTMLElement

  if (currentLine) {
    const containerHeight = container.clientHeight
    const lineTop = currentLine.offsetTop
    const lineHeight = currentLine.clientHeight
    const scrollTop = lineTop - containerHeight / 2 + lineHeight / 2

    container.scrollTo({
      top: scrollTop,
      behavior: 'smooth',
    })
  }
}

const updateCurrentLine = () => {
  const player = playback('current')
  const currentTime = player?.player?.media?.currentTime || 0
  let newIndex = -1

  for (let i = props.parsedLyrics.length - 1; i >= 0; i--) {
    if (currentTime >= props.parsedLyrics[i].time) {
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
  if (props.parsedLyrics.length > 0 && typeof window !== 'undefined') {
    timeUpdateInterval = window.setInterval(updateCurrentLine, 100)
  }
}

watch(() => props.parsedLyrics, () => {
  currentLineIndex.value = -1
  startTimeUpdates()
}, { immediate: true, deep: true })

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', stopTimeUpdates)
  }
})

onBeforeUnmount(() => {
  stopTimeUpdates()
  if (typeof window !== 'undefined') {
    window.removeEventListener('beforeunload', stopTimeUpdates)
  }
})
</script>

<style lang="postcss" scoped>
.lyrics-synced {
  font-size: v-bind(fontSize);
}
</style>
