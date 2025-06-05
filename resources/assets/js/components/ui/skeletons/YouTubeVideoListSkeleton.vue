<template>
  <div class="skeleton space-y-5">
    <YouTubeVideoListItemSkeleton v-for="i in 4" :key="i" />
  </div>
</template>

<script setup lang="ts">
import YouTubeVideoListItemSkeleton from '@/components/ui/skeletons/YouTubeVideoListItemSkeleton.vue'
</script>
