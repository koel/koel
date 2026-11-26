<template>
  <li class="skeleton">
    <div class="pulse h-9 rounded-full" :style="{ width: `${width}px` }" />
  </li>
</template>

<script setup lang="ts">
defineProps<{ width: number }>()
</script>
