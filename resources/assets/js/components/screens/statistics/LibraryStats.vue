<template>
  <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <StatTile
      v-for="stat in stats"
      :key="stat.key"
      :label="stat.label"
      :tone="toneOf(stat)"
      :value="formatAmount(stat, stat.value)"
    >
      <dd v-if="stat.limit" class="flex flex-col gap-1 mt-1">
        <UsageMeter :aria-label="stat.label" :limit="stat.limit" :used="stat.used ?? stat.value" />
        <span class="text-sm text-k-fg-50">of {{ formatAmount(stat, stat.limit) }}</span>
      </dd>
    </StatTile>
  </dl>
</template>

<script lang="ts" setup>
import { formatBytes } from '@/utils/formatters'
import type { StatTileTone } from '@/components/screens/statistics/StatTile.vue'

import StatTile from '@/components/screens/statistics/StatTile.vue'
import UsageMeter from '@/components/ui/UsageMeter.vue'

defineProps<{ stats: LibraryStat[] }>()

const tonesByKey: Record<string, StatTileTone> = {
  songs: 'primary',
  albums: 'success',
  artists: 'love',
  podcasts: 'warning',
  episodes: 'warning',
  radio_stations: 'danger',
  storage: 'highlight',
}

const toneOf = (stat: LibraryStat) => tonesByKey[stat.key] ?? 'highlight'

const formatAmount = (stat: LibraryStat, amount: number) =>
  stat.unit === 'bytes' ? formatBytes(amount) : amount.toLocaleString()
</script>
