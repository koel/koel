<template>
  <svg
    :aria-valuenow="Math.round(value)"
    aria-valuemax="100"
    aria-valuemin="0"
    class="-rotate-90"
    role="progressbar"
    viewBox="0 0 24 24"
  >
    <circle :stroke-width="thickness" class="stroke-k-fg-20" cx="12" cy="12" fill="none" :r="RADIUS" />
    <circle
      :r="RADIUS"
      :stroke-dasharray="CIRCUMFERENCE"
      :stroke-dashoffset="CIRCUMFERENCE * (1 - value / 100)"
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
const RADIUS = 9
const CIRCUMFERENCE = 2 * Math.PI * RADIUS

withDefaults(defineProps<{ value: number; thickness?: number }>(), { thickness: 3 })
</script>
