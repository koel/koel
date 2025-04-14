<template>
  <span class="flex w-[64px] border border-solid border-white/20 rounded-md overflow-hidden mt-[0.5rem] md:mt-0">
    <label
      v-koel-tooltip
      :class="{ active: value === 'name' }"
      data-testid="sort-mode-name"
      title="Sort by name"
    >
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="name">
      <ALargeSmallIcon size="16" />
      <span class="hidden">Sort by name</span>
    </label>

    <label
      v-koel-tooltip
      :class="{ active: value === 'year' }"
      data-testid="sort-mode-year"
      title="Sort by release year"
    >
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="year">
      <CalendarFoldIcon size="16" />
      <span class="hidden">Sort by release year</span>
    </label>
  </span>
</template>

<script lang="ts" setup>
import { ALargeSmallIcon, CalendarFoldIcon } from 'lucide-vue-next'
import { computed } from 'vue'

const props = withDefaults(defineProps<{ modelValue?: AlbumSortMode }>(), { modelValue: 'name' })

const emit = defineEmits<{ (e: 'update:modelValue', value: AlbumSortMode): void }>()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
})
</script>

<style lang="postcss" scoped>
label {
  @apply w-1/2 flex items-center justify-center h-[2rem] mb-0 cursor-pointer;

  &.active {
    @apply bg-k-text-primary text-k-bg-primary;
  }
}
</style>
