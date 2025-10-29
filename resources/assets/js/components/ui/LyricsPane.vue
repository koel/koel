<template>
  <article>
    <main class="relative">
      <template v-if="song">
        <div v-show="song.lyrics">
          <SyncLyricsPane v-if="isSyncedLyrics" :parsed-lyrics="parsedLyrics" :font-size="fontSize" />
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
import { computed, defineAsyncComponent, ref, toRefs, watch } from 'vue'
import { cr2lf } from '@/utils/formatters'
import { eventBus } from '@/utils/eventBus'
import { usePolicies } from '@/composables/usePolicies'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import SyncLyricsPane from '@/components/ui/SyncLyricsPane.vue'

const props = defineProps<{ song: Song }>()

const Magnifier = defineAsyncComponent(() => import('@/components/ui/Magnifier.vue'))

const { song } = toRefs(props)

const { currentUserCan } = usePolicies()

const canUpdateLyrics = currentUserCan.editSong(song.value)
const zoomLevel = ref(preferences.lyrics_zoom_level || 1)

interface LyricLine {
  time: number
  text: string
}

const lyrics = computed(() => cr2lf(song.value.lyrics))
const fontSize = computed(() => `${1 + (zoomLevel.value - 1) * 0.2}rem`)

// Parse LRC format lyrics
const parsedLyrics = computed<LyricLine[]>(() => {
  const lyricsText = lyrics.value
  if (!lyricsText) {
    return []
  }

  const allLines = lyricsText.split('\n')
  const linesWithTimestamps: Array<{ time: number, text: string, originalIndex: number }> = []
  const lrcRegex = /\[(\d{2}):(\d{2})\.(\d{2,3})\](.*)/

  // First pass: collect lines with timestamps
  allLines.forEach((line, index) => {
    const match = line.match(lrcRegex)
    if (match) {
      const minutes = Number.parseInt(match[1], 10)
      const seconds = Number.parseInt(match[2], 10)
      const centiseconds = Number.parseInt(match[3].padEnd(2, '0').slice(0, 2), 10)
      const time = minutes * 60 + seconds + centiseconds / 100
      const text = match[4].trim()

      if (text) {
        linesWithTimestamps.push({ time, text, originalIndex: index })
      }
    }
  })

  // If no timestamped lines found, this is not LRC format
  if (linesWithTimestamps.length === 0) {
    return []
  }

  // Second pass: handle lines without timestamps
  const result: LyricLine[] = []
  allLines.forEach((line, index) => {
    const timestampedLine = linesWithTimestamps.find(l => l.originalIndex === index)

    if (timestampedLine) {
      result.push({ time: timestampedLine.time, text: timestampedLine.text })
    } else {
      const trimmedLine = line.replace(/\[.*?\]/, '').trim()
      if (trimmedLine) {
        // Find previous and next timestamped lines
        const prevTimestamped = linesWithTimestamps.filter(l => l.originalIndex < index).at(-1)
        const nextTimestamped = linesWithTimestamps.find(l => l.originalIndex > index)

        let inferredTime: number
        if (!prevTimestamped) {
          // Very first line - use 00:00:00
          inferredTime = 0
        } else if (!nextTimestamped) {
          // Last line - use previous line's timestamp
          inferredTime = prevTimestamped.time
        } else {
          // Between two lines - use average
          inferredTime = (prevTimestamped.time + nextTimestamped.time) / 2
        }

        result.push({ time: inferredTime, text: trimmedLine })
      }
    }
  })

  return result.sort((a, b) => a.time - b.time)
})

const isSyncedLyrics = computed(() => parsedLyrics.value.length > 0)

const zoomIn = () => (zoomLevel.value = Math.min(zoomLevel.value + 1, 8))
const zoomOut = () => (zoomLevel.value = Math.max(zoomLevel.value - 1, -2))
const showEditSongForm = () => eventBus.emit('MODAL_SHOW_EDIT_SONG_FORM', song.value, 'lyrics')

watch(zoomLevel, level => (preferences.lyrics_zoom_level = level), { immediate: true })
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
</style>
