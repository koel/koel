<template>
  <div class="skeleton space-y-5">
    <YouTubeVideoItemSkeleton v-for="i in 4" :key="i" />
  </div>
</template>

<script setup lang="ts">
import YouTubeVideoItemSkeleton from '@/components/ui/youtube/YouTubeVideoItemSkeleton.vue'
</script>
