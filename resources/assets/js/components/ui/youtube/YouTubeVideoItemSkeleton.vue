<template>
  <div class="skeleton flex gap-3">
    <div class="w-[90px] h-[60px] pulse" />
    <aside class="space-y-1 flex-1">
      <ParagraphSkeleton :lines="3" />
    </aside>
  </div>
</template>

<script setup lang="ts">
import ParagraphSkeleton from '@/components/ui/ParagraphSkeleton.vue'
</script>
