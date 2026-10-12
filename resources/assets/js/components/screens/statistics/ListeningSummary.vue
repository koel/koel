<template>
  <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div v-for="figure in figures" :key="figure.label" class="flex flex-col gap-1 p-4 rounded-md bg-k-fg-5">
      <dt class="text-sm text-k-fg-70">{{ figure.label }}</dt>
      <dd class="text-2xl text-k-fg tabular-nums">{{ figure.value }}</dd>
      <dd v-if="figure.note" class="text-sm text-k-fg-50" data-testid="figure-note">{{ figure.note }}</dd>
    </div>
  </dl>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { pluralize, secondsToHumanReadable } from '@/utils/formatters'

const props = defineProps<{ statistics: ListeningStatistics }>()

const describeChange = (current: number, previous?: number) => {
  if (!previous) {
    return undefined
  }

  const change = Math.round(((current - previous) / previous) * 100)

  return `${change >= 0 ? '+' : '−'}${Math.abs(change)}%`
}

const figures = computed(() => {
  const { summary, previous_summary, discoveries, streak } = props.statistics

  return [
    {
      label: 'Plays',
      value: summary.plays.toLocaleString(),
      note: describeChange(summary.plays, previous_summary?.plays),
    },
    {
      label: 'Listening time',
      value: secondsToHumanReadable(summary.listening_time),
      note: describeChange(summary.listening_time, previous_summary?.listening_time),
    },
    {
      label: 'Songs',
      value: summary.song_count.toLocaleString(),
      note: describeChange(summary.song_count, previous_summary?.song_count),
    },
    {
      label: 'Artists',
      value: summary.artist_count.toLocaleString(),
      note: describeChange(summary.artist_count, previous_summary?.artist_count),
    },
    { label: 'Current streak', value: pluralize(streak.current_days, 'day') },
    { label: 'Longest streak', value: pluralize(streak.longest_days, 'day') },
    ...(discoveries
      ? [
          { label: 'New songs', value: discoveries.song_count.toLocaleString() },
          { label: 'New artists', value: discoveries.artist_count.toLocaleString() },
        ]
      : []),
  ]
})
</script>
