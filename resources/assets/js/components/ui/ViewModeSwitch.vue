<template>
  <span
    class="flex w-[64px] border border-solid border-white/20 rounded-md overflow-hidden"
  >
    <label
      v-koel-tooltip
      :class="{ active: value === 'thumbnails' }"
      class="thumbnails"
      data-testid="view-mode-thumbnails"
      title="View as thumbnails"
    >
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="thumbnails">
      <LayoutGridIcon :size="16" />
      <span class="hidden">View as thumbnails</span>
    </label>

    <label
      v-koel-tooltip
      :class="{ active: value === 'list' }"
      class="list"
      data-testid="view-mode-list"
      title="View as list"
    >
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="list">
      <LayoutListIcon :size="16" />
      <span class="hidden">View as list</span>
    </label>
  </span>
</template>

<script lang="ts" setup>
import { LayoutGridIcon, LayoutListIcon } from 'lucide-vue-next'
import { computed } from 'vue'

const props = withDefaults(defineProps<{ modelValue?: ViewMode }>(), { modelValue: 'thumbnails' })

const emit = defineEmits<{ (e: 'update:modelValue', value: ViewMode): void }>()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
})
</script>

<style lang="postcss" scoped>
label {
  @apply w-1/2 flex items-center justify-center h-full mb-0 cursor-pointer;

  &.active {
    @apply bg-k-text-primary text-k-bg-primary;
  }
}
</style>
