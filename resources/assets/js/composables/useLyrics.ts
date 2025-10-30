import type { Ref } from 'vue'
import { computed, ref, watch } from 'vue'
import { cr2lf } from '@/utils/formatters'
import { usePolicies } from '@/composables/usePolicies'

export const useLyrics = (songRef: Ref<Song>) => {
  const { currentUserCan } = usePolicies()

  const plainTextLyrics = ref('')
  const lrcLyrics = ref<LrcLine[]>([])
  const userCanUpdateLyrics = ref(false)

  const isLrc = computed(() => lrcLyrics.value.length > 0)
  const hasLyrics = computed(() => Boolean(plainTextLyrics.value))

  watch(songRef, song => {
    userCanUpdateLyrics.value = currentUserCan.editSong(song)
    plainTextLyrics.value = ''
    lrcLyrics.value = []

    const allPlainLines: string[] = []

    const allLines = cr2lf(song.lyrics).trim().split('\n')
    const linesWithTimestamps: Array<{ time: number, text: string, originalIndex: number }> = []
    const lrcRegex = /\[(\d{2}):(\d{2})\.(\d{2,3})\](.*)/

    // First pass: collect lines with timestamps
    allLines.forEach((line, index) => {
      const match = line.trim().match(lrcRegex)

      if (match) {
        const minutes = Number.parseInt(match[1], 10)
        const seconds = Number.parseInt(match[2], 10)
        const centiseconds = Number.parseInt(match[3].padEnd(2, '0').slice(0, 2), 10)
        const time = minutes * 60 + seconds + centiseconds / 100
        const text = match[4].trim()

        if (text) {
          linesWithTimestamps.push({ time, text, originalIndex: index })
          allPlainLines.push(text)
        }
      } else {
        allPlainLines.push(line.trim())
      }
    })

    plainTextLyrics.value = allPlainLines.join('\n')

    // If no timestamped lines found, this is not LRC format
    if (linesWithTimestamps.length === 0) {
      return
    }

    // Second pass: handle lines without timestamps
    const result: LrcLine[] = []

    allLines.forEach((line, index) => {
      const timestampedLine = linesWithTimestamps.find(({ originalIndex }) => originalIndex === index)

      if (timestampedLine) {
        result.push({ time: timestampedLine.time, text: timestampedLine.text })
        return
      }

      const trimmedLine = line.replace(/\[.*?\]/, '').trim()

      if (!trimmedLine) {
        return
      }

      // Find previous and next timestamped lines
      const prevTimestamp = linesWithTimestamps.filter(l => l.originalIndex < index).at(-1)?.time
      const nextTimestamp = linesWithTimestamps.find(l => l.originalIndex > index)?.time

      let inferredTime: number

      if (!prevTimestamp) {
        // Very first line - use 00:00.00
        inferredTime = 0
      } else if (!nextTimestamp) {
        // Last line - use the previous line's timestamp
        inferredTime = prevTimestamp
      } else {
        // Between two lines - use average
        inferredTime = (prevTimestamp + nextTimestamp) / 2
      }

      result.push({ time: inferredTime, text: trimmedLine })
    })

    lrcLyrics.value = result.sort((a, b) => a.time - b.time)
  }, { immediate: true, deep: true })

  return {
    hasLyrics,
    isLrc,
    plainTextLyrics,
    lrcLyrics,
    userCanUpdateLyrics,
  }
}
