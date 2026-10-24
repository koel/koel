<template>
  <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <StatisticTile
      v-for="statistic in statistics"
      :key="statistic.key"
      :label="statistic.label"
      :tone="toneOf(statistic)"
      :value="formatAmount(statistic, statistic.value)"
    >
      <dd v-if="statistic.limit" class="flex flex-col gap-1 mt-1">
        <UsageMeter :aria-label="statistic.label" :limit="statistic.limit" :used="statistic.used ?? statistic.value" />
        <span class="text-sm text-k-fg-50">of {{ formatAmount(statistic, statistic.limit) }}</span>
      </dd>
    </StatisticTile>
  </dl>
</template>

<script lang="ts" setup>
import { formatBytes } from '@/utils/formatters'
import type { StatisticTileTone } from '@/components/screens/statistics/StatisticTile.vue'

import StatisticTile from '@/components/screens/statistics/StatisticTile.vue'
import UsageMeter from '@/components/ui/UsageMeter.vue'

defineProps<{ statistics: LibraryStatistic[] }>()

const tonesByKey: Record<string, StatisticTileTone> = {
  songs: 'primary',
  albums: 'success',
  artists: 'love',
  podcasts: 'warning',
  episodes: 'warning',
  radio_stations: 'danger',
  storage: 'highlight',
}

const toneOf = (statistic: LibraryStatistic) => tonesByKey[statistic.key] ?? 'highlight'

const formatAmount = (statistic: LibraryStatistic, amount: number) =>
  statistic.unit === 'bytes' ? formatBytes(amount) : amount.toLocaleString()
</script>
