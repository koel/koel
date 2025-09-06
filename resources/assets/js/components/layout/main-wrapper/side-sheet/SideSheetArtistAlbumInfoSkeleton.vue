<template>
  <article class="skeleton flex flex-col gap-6">
    <div class="h-8 pulse" />
    <div class="aspect-square rounded-lg pulse" />

    <ParagraphSkeleton />
  </article>
</template>

<script setup lang="ts">
import ParagraphSkeleton from '@/components/ui/ParagraphSkeleton.vue'
</script>
