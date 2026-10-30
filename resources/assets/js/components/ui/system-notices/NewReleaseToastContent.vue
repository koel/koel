<template>
  <span>{{ appName }} {{ version }} is available.</span>
  <a :href="releaseUrl" class="text-k-fg-70 hover:text-k-fg" rel="noopener" target="_blank">See what's new</a>
</template>

<script lang="ts" setup>
defineProps<{ appName: string; version: string; releaseUrl: string }>()
</script>
