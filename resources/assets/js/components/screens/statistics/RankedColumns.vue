<template>
  <ol :style="{ gridTemplateRows: `repeat(${rowCount}, auto)` }" class="grid md:grid-flow-col md:grid-cols-3 gap-x-8">
    <li
      v-for="(item, i) in items"
      :key="item.key"
      :class="{ 'last-in-column': isLastInColumn(i) }"
      class="flex items-start gap-3 py-2.5 border-b border-k-fg-5"
    >
      <img v-if="item.image" :src="item.image" alt="" class="w-12 h-12 rounded-md object-cover" />
      <span class="w-5 text-right font-medium text-k-fg tabular-nums">{{ i + 1 }}</span>
      <div class="flex-1 min-w-0">
        <a :href="item.href" class="block truncate font-medium text-k-fg hover:text-k-highlight">{{ item.title }}</a>
        <p class="truncate text-k-fg-70">{{ item.subtitle }}</p>
      </div>
    </li>
  </ol>
</template>

<script lang="ts" setup>
import { computed } from 'vue'

export interface RankedItem {
  key: string
  title: string
  subtitle: string
  href: string
  image?: string
}

const props = defineProps<{ items: RankedItem[] }>()

const COLUMN_COUNT = 3

const rowCount = computed(() => Math.ceil(props.items.length / COLUMN_COUNT))

const isLastInColumn = (index: number) => (index + 1) % rowCount.value === 0 || index === props.items.length - 1
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

@media (min-width: 768px) {
  .last-in-column {
    @apply border-b-transparent;
  }
}
</style>
