<template>
  <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <StatTile
      v-for="fact in facts"
      :key="fact.key"
      :label="fact.label"
      :tone="toneOf(fact)"
      :value="formatAmount(fact, fact.value)"
    >
      <dd v-if="fact.limit" class="flex flex-col gap-1 mt-1">
        <UsageMeter :aria-label="fact.label" :limit="fact.limit" :used="fact.used ?? fact.value" />
        <span class="text-sm text-k-fg-50">of {{ formatAmount(fact, fact.limit) }}</span>
      </dd>
    </StatTile>
  </dl>
</template>

<script lang="ts" setup>
import { formatBytes } from '@/utils/formatters'
import type { StatTileTone } from '@/components/screens/statistics/StatTile.vue'

import StatTile from '@/components/screens/statistics/StatTile.vue'
import UsageMeter from '@/components/ui/UsageMeter.vue'

defineProps<{ facts: LibraryFact[] }>()

const tonesByKey: Record<string, StatTileTone> = {
  songs: 'primary',
  albums: 'success',
  artists: 'love',
  podcasts: 'warning',
  episodes: 'warning',
  radio_stations: 'danger',
  storage: 'highlight',
}

const toneOf = (fact: LibraryFact) => tonesByKey[fact.key] ?? 'highlight'

const formatAmount = (fact: LibraryFact, amount: number) =>
  fact.unit === 'bytes' ? formatBytes(amount) : amount.toLocaleString()
</script>
