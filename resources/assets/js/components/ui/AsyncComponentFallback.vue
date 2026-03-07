<template>
  <div class="h-full w-full flex items-center justify-center text-k-fg-50">
    <div class="flex flex-col items-center">
      <span class="block text-[6rem] mb-4">
        <WifiOff :size="64" />
      </span>
      <span class="text-3xl font-light">You're offline.</span>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { WifiOff } from 'lucide-vue-next'
</script>
