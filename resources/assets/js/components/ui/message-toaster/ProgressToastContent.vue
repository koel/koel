<template>
  <span class="flex items-center gap-2">
    <ProgressRing :value class="size-4 shrink-0 text-k-highlight" />
    <span>{{ label }}</span>
  </span>
</template>

<script lang="ts" setup>
import ProgressRing from '@/components/ui/ProgressRing.vue'

defineProps<{ label: string; value: number }>()
</script>
