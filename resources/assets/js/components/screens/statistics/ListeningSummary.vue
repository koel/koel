<template>
  <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <StatisticTile
      v-for="figure in figures"
      :key="figure.label"
      :label="figure.label"
      :tone="figure.tone"
      :value="figure.value"
    >
      <dd v-if="figure.note" class="text-sm text-k-fg-50" data-testid="figure-note">{{ figure.note }}</dd>
    </StatisticTile>
  </dl>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { pluralize, secondsToHumanReadable } from '@/utils/formatters'

import StatisticTile from '@/components/screens/statistics/StatisticTile.vue'

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
      tone: 'highlight' as const,
      value: summary.plays.toLocaleString(),
      note: describeChange(summary.plays, previous_summary?.plays),
    },
    {
      label: 'Listening time',
      tone: 'success' as const,
      value: secondsToHumanReadable(summary.listening_time),
      note: describeChange(summary.listening_time, previous_summary?.listening_time),
    },
    {
      label: 'Songs',
      tone: 'primary' as const,
      value: summary.song_count.toLocaleString(),
      note: describeChange(summary.song_count, previous_summary?.song_count),
    },
    {
      label: 'Artists',
      tone: 'love' as const,
      value: summary.artist_count.toLocaleString(),
      note: describeChange(summary.artist_count, previous_summary?.artist_count),
    },
    {
      label: 'Current streak',
      tone: 'warning' as const,
      value: pluralize(streak.current_days, 'day'),
    },
    {
      label: 'Longest streak',
      tone: 'warning' as const,
      value: pluralize(streak.longest_days, 'day'),
    },
    ...(discoveries
      ? [
          {
            label: 'New songs',
            tone: 'primary' as const,
            value: discoveries.song_count.toLocaleString(),
          },
          {
            label: 'New artists',
            tone: 'love' as const,
            value: discoveries.artist_count.toLocaleString(),
          },
        ]
      : []),
  ]
})
</script>
