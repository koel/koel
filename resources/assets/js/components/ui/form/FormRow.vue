<template>
  <div v-if="cols === 1" class="flex flex-col gap-2">
    <label>
      <span v-if="$slots.label" class="block mb-1.5">
        <slot name="label" />
      </span>

      <slot />
    </label>

    <small v-if="$slots.help" class="text-[.95rem] opacity-70 mt-0.5">
      <slot v-if="$slots.help" name="help" />
    </small>
  </div>

  <div v-else :class="columnClass" class="grid gap-3">
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
