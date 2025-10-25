<template>
  <article>
    <main class="relative">
      <template v-if="song">
        <div v-show="song.lyrics">
          <div v-if="isSyncedLyrics" ref="lyricsContainer" class="lyrics-synced overflow-y-auto max-h-[70vh]">
            <div
              v-for="(line, index) in parsedLyrics"
              :key="index"
              :class="['lyrics-line leading-relaxed transition-all duration-300', { 'active': index === currentLineIndex }]"
            >
              {{ line.text }}
            </div>
          </div>
          <div v-else class="lyrics whitespace-pre-wrap leading-relaxed">{{ lyrics }}</div>
          <span class="magnifier-wrapper opacity-0 absolute top-0 right-0 hover:!opacity-100">
            <Magnifier @in="zoomIn" @out="zoomOut" />
          </span>
        </div>
        <p v-if="song.id && !song.lyrics">
          <template v-if="canUpdateLyrics">
            No lyrics found.
            <a role="button" @click.prevent="showEditSongForm">Click here</a>
            to add lyrics.
          </template>
          <span v-else>No lyrics available. Are you listening to Bach?</span>
        </p>
      </template>
    </main>
  </article>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, nextTick, ref, toRefs, watch } from 'vue'
import { cr2lf } from '@/utils/formatters'
import { eventBus } from '@/utils/eventBus'
import { usePolicies } from '@/composables/usePolicies'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { playback } from '@/services/playbackManager'

const props = defineProps<{ song: Song }>()

const Magnifier = defineAsyncComponent(() => import('@/components/ui/Magnifier.vue'))

const { song } = toRefs(props)

const { currentUserCan } = usePolicies()

const canUpdateLyrics = currentUserCan.editSong(song.value)
const zoomLevel = ref(preferences.lyrics_zoom_level || 1)
const lyricsContainer = ref<HTMLElement>()
const currentLineIndex = ref(-1)

interface LyricLine {
  time: number
  text: string
}

const lyrics = computed(() => cr2lf(song.value.lyrics))
const fontSize = computed(() => `${1 + (zoomLevel.value - 1) * 0.2}rem`)

// Parse LRC format lyrics
const parsedLyrics = computed<LyricLine[]>(() => {
  const lyricsText = lyrics.value
  if (!lyricsText) return []

  const lines: LyricLine[] = []
  const lrcRegex = /\[(\d{2}):(\d{2})\.(\d{2,3})\](.*)/g
  let match

  while ((match = lrcRegex.exec(lyricsText)) !== null) {
    const minutes = parseInt(match[1], 10)
    const seconds = parseInt(match[2], 10)
    const centiseconds = parseInt(match[3].padEnd(2, '0').slice(0, 2), 10)
    const time = minutes * 60 + seconds + centiseconds / 100
    const text = match[4].trim()

    if (text) {
      lines.push({ time, text })
    }
  }

  return lines.sort((a, b) => a.time - b.time)
})

const isSyncedLyrics = computed(() => parsedLyrics.value.length > 0)

const zoomIn = () => (zoomLevel.value = Math.min(zoomLevel.value + 1, 8))
const zoomOut = () => (zoomLevel.value = Math.max(zoomLevel.value - 1, -2))
const showEditSongForm = () => eventBus.emit('MODAL_SHOW_EDIT_SONG_FORM', song.value, 'lyrics')

// Update current line based on playback position
const updateCurrentLine = () => {
  if (!isSyncedLyrics.value) return

  const player = playback('current')
  const currentTime = player?.player?.media?.currentTime || 0
  let newIndex = -1

  for (let i = parsedLyrics.value.length - 1; i >= 0; i--) {
    if (currentTime >= parsedLyrics.value[i].time) {
      newIndex = i
      break
    }
  }

  if (newIndex !== currentLineIndex.value) {
    currentLineIndex.value = newIndex
    scrollToCurrentLine()
  }
}

// Scroll to keep current line visible
const scrollToCurrentLine = async () => {
  if (!lyricsContainer.value || currentLineIndex.value < 0) return

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
      behavior: 'smooth'
    })
  }
}

// Watch for playback time changes
let timeUpdateInterval: number | null = null

watch(() => song.value.id, () => {
  currentLineIndex.value = -1
  if (timeUpdateInterval) clearInterval(timeUpdateInterval)

  if (isSyncedLyrics.value) {
    timeUpdateInterval = window.setInterval(updateCurrentLine, 100)
  }
}, { immediate: true })

watch(zoomLevel, level => (preferences.lyrics_zoom_level = level), { immediate: true })

// Cleanup
if (typeof window !== 'undefined') {
  window.addEventListener('beforeunload', () => {
    if (timeUpdateInterval) clearInterval(timeUpdateInterval)
  })
}
</script>

<style lang="postcss" scoped>
main {
  .magnifier-wrapper {
    @apply no-hover:opacity-100;
  }

  &:hover .magnifier-wrapper {
    @apply opacity-50;
  }
}

.lyrics {
  font-size: v-bind(fontSize);
}

.lyrics-synced {
  font-size: v-bind(fontSize);
}

.lyrics-line {
  padding: 0.5rem 1rem;
  opacity: 0.5;
  text-align: center;
  transition: all 0.3s ease;

  &.active {
    opacity: 1;
    font-weight: 600;
    transform: scale(1.05);
    color: var(--color-highlight);
  }
}
</style>
