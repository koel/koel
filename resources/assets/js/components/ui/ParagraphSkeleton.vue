<template>
  <div class="skeleton flex flex-col gap-3">
    <p v-for="i in (lines - 1)" :key="i" class="h-5 pulse" />
    <p class="h-5 w-1/3 pulse" />
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ lines?: number }>(), { lines: 4 })
</script>
