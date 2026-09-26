<template>
  <div v-bind="meterAttributes" class="h-2 rounded-full bg-k-fg-10 overflow-hidden">
    <div :style="{ clipPath: `inset(0 ${100 - percentUsed}% 0 0 round 9999px)` }" class="bar h-full" />
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'

const props = defineProps<{ used: number; limit: number }>()

const percentUsed = computed(() => (props.limit ? Math.min(100, (props.used / props.limit) * 100) : 0))

const meterAttributes = computed(() =>
  props.limit > 0
    ? {
        role: 'meter',
        'aria-valuemin': 0,
        'aria-valuemax': props.limit,
        'aria-valuenow': Math.min(props.used, props.limit),
      }
    : {},
)
</script>

<style lang="postcss" scoped>
.bar {
  background: linear-gradient(to right, var(--color-success), var(--color-warning), var(--color-danger));
}
</style>
