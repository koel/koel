<template>
  <label
    v-if="cols === 1"
    class="flex flex-col gap-2 text-[1.1rem]"
  >
    <span v-if="$slots.label">
      <slot name="label" />
    </span>
    <slot />
    <span v-if="$slots.help" class="text-[.95rem] opacity-70 mt-1.5">
      <slot v-if="$slots.help" name="help" />
    </span>
  </label>

  <div v-else class="grid gap-3" :class="columnClass">
    <slot />
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'

const props = withDefaults(defineProps<{ cols?: number }>(), { cols: 1 })

const columnClass = computed(() => {
  switch (props.cols) {
    case 1:
      return 'grid-cols-1'
    case 2:
      return 'md:grid-cols-2'
    case 3:
      return 'md:grid-cols-3'
    case 4:
      return 'md:grid-cols-4'
    default:
      throw new Error('Only 1-4 columns are supported')
  }
})
</script>
