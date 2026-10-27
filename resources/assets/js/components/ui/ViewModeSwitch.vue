<template>
  <span class="relative flex gap-1 w-[64px] p-[3px] rounded-md bg-k-fg-10 shadow-sm">
    <span :class="{ secondary: value !== 'grid' }" class="indicator" />
    <label :class="{ active: value === 'grid' }" data-testid="view-mode-grid">
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="grid" />
      <LayoutGridIcon :size="16" />
      <span class="sr-only">View as grid</span>
    </label>

    <label v-if="secondary === 'table'" :class="{ active: value === 'table' }" data-testid="view-mode-table">
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="table" />
      <TableIcon :size="16" />
      <span class="sr-only">View as table</span>
    </label>

    <label v-else :class="{ active: value === 'list' }" data-testid="view-mode-list">
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="list" />
      <LayoutListIcon :size="16" />
      <span class="sr-only">View as list</span>
    </label>
  </span>
</template>

<script lang="ts" setup>
import { LayoutGridIcon, LayoutListIcon, TableIcon } from 'lucide-vue-next'

withDefaults(defineProps<{ secondary?: 'list' | 'table' }>(), { secondary: 'list' })

const value = defineModel<ViewMode>({ default: 'grid' })
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
label {
  @apply relative flex-1 min-w-0 flex items-center justify-center py-[4.5px] mb-0 rounded-sm cursor-pointer text-k-fg-70 hover:text-k-fg;

  &.active {
    @apply text-k-fg;
  }
}

.indicator {
  @apply absolute inset-y-[3px] left-[3px] w-[calc(50%-5px)] rounded-sm bg-k-fg-20 shadow-sm transition-all duration-200 ease-out;

  &.secondary {
    @apply translate-x-[calc(100%+4px)];
  }
}
</style>
