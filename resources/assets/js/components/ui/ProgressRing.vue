<template>
  <svg
    :aria-valuenow="Math.round(value)"
    aria-valuemax="100"
    aria-valuemin="0"
    class="-rotate-90"
    role="progressbar"
    viewBox="0 0 24 24"
  >
    <circle :stroke-width="thickness" class="stroke-k-fg-20" cx="12" cy="12" fill="none" :r="radius" />
    <circle
      :r="radius"
      :stroke-dasharray="circumference"
      :stroke-dashoffset="circumference * (1 - value / 100)"
      class="stroke-current transition-[stroke-dashoffset] duration-200"
      cx="12"
      cy="12"
      fill="none"
      :stroke-width="thickness"
      stroke-linecap="round"
    />
  </svg>
</template>

<script lang="ts" setup>
import { computed } from 'vue'

const HALF_SIZE = 12

const props = withDefaults(defineProps<{ value: number; thickness?: number }>(), { thickness: 3 })

const radius = computed(() => HALF_SIZE - props.thickness / 2)
const circumference = computed(() => 2 * Math.PI * radius.value)
</script>
