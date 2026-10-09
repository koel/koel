<template>
  <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div v-for="figure in figures" :key="figure.label" class="flex flex-col gap-1 p-4 rounded-md bg-k-fg-5">
      <dt class="text-sm text-k-fg-70">{{ figure.label }}</dt>
      <dd class="text-2xl text-k-fg tabular-nums">{{ figure.value }}</dd>
    </div>
  </dl>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { secondsToHumanReadable } from '@/utils/formatters'

const props = defineProps<{ summary: ListeningStatistics['summary'] }>()

const figures = computed(() => [
  { label: 'Plays', value: props.summary.plays.toLocaleString() },
  { label: 'Listening time', value: secondsToHumanReadable(props.summary.listening_time) },
  { label: 'Songs', value: props.summary.song_count.toLocaleString() },
  { label: 'Artists', value: props.summary.artist_count.toLocaleString() },
])
</script>
