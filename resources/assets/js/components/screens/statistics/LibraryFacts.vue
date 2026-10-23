<template>
  <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div v-for="fact in facts" :key="fact.key" class="flex flex-col gap-1 p-4 rounded-md bg-k-fg-5">
      <dt class="text-sm text-k-fg-70">{{ fact.label }}</dt>
      <dd class="text-2xl text-k-fg tabular-nums">{{ formatAmount(fact, fact.value) }}</dd>
      <dd v-if="fact.limit" class="flex flex-col gap-1">
        <UsageMeter :aria-label="fact.label" :limit="fact.limit" :used="fact.used ?? fact.value" />
        <span class="text-sm text-k-fg-50">of {{ formatAmount(fact, fact.limit) }}</span>
      </dd>
    </div>
  </dl>
</template>

<script lang="ts" setup>
import { formatBytes } from '@/utils/formatters'

import UsageMeter from '@/components/ui/UsageMeter.vue'

defineProps<{ facts: LibraryFact[] }>()

const formatAmount = (fact: LibraryFact, amount: number) =>
  fact.unit === 'bytes' ? formatBytes(amount) : amount.toLocaleString()
</script>
