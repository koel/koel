<template>
  <div
    :aria-valuemax="limit"
    :aria-valuenow="used"
    aria-valuemin="0"
    class="h-2 rounded-full bg-k-fg-10 overflow-hidden"
    role="meter"
  >
    <div :style="{ clipPath: `inset(0 ${100 - percentUsed}% 0 0 round 9999px)` }" class="bar h-full" />
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'

const props = defineProps<{ used: number; limit: number }>()

const percentUsed = computed(() => (props.limit ? Math.min(100, (props.used / props.limit) * 100) : 0))
</script>

<style lang="postcss" scoped>
.bar {
  background: linear-gradient(to right, var(--color-success), var(--color-warning), var(--color-danger));
}
</style>
