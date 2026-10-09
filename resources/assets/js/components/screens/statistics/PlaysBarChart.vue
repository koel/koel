<template>
  <figure class="flex flex-col gap-3">
    <figcaption class="text-k-fg">{{ title }}</figcaption>
    <div class="flex items-end gap-1 h-32" data-testid="plays-bars">
      <div
        v-for="bar in bars"
        :key="bar.key"
        :style="{ height: `${heightOf(bar)}%` }"
        :title="`${bar.label}: ${pluralize(bar.plays, 'play')}`"
        class="flex-1 min-h-0.5 rounded-t-sm bg-k-highlight"
      />
    </div>
    <div class="flex gap-1 text-xs text-k-fg-50" aria-hidden="true">
      <span v-for="(bar, i) in bars" :key="bar.key" class="flex-1 text-center truncate">
        {{ i % labelStep === 0 ? bar.label : '' }}
      </span>
    </div>
  </figure>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import type { PlaysBar } from '@/utils/listeningStatistics'
import { pluralize } from '@/utils/formatters'

const props = defineProps<{ title: string; bars: PlaysBar[] }>()

const MAX_LABELS = 12

const highestPlays = computed(() => Math.max(1, ...props.bars.map(bar => bar.plays)))
const labelStep = computed(() => Math.ceil(props.bars.length / MAX_LABELS))

const heightOf = (bar: PlaysBar) => (bar.plays / highestPlays.value) * 100
</script>
