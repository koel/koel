<template>
  <span class="flex w-[64px] border border-solid border-k-fg-20 rounded-md overflow-hidden">
    <label v-koel-tooltip :class="{ active: value === 'grid' }" data-testid="view-mode-grid" title="View as grid">
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="grid" />
      <LayoutGridIcon :size="16" />
      <span class="hidden">View as grid</span>
    </label>

    <label
      v-if="secondary === 'table'"
      v-koel-tooltip
      :class="{ active: value === 'table' }"
      data-testid="view-mode-table"
      title="View as table"
    >
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="table" />
      <TableIcon :size="16" />
      <span class="hidden">View as table</span>
    </label>

    <label
      v-else
      v-koel-tooltip
      :class="{ active: value === 'list' }"
      data-testid="view-mode-list"
      title="View as list"
    >
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="list" />
      <LayoutListIcon :size="16" />
      <span class="hidden">View as list</span>
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
  @apply flex-1 min-w-0 flex items-center justify-center py-2 mb-0 cursor-pointer;

  &.active {
    @apply bg-k-fg text-k-bg;
  }
}
</style>
