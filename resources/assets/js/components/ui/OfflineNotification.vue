<template>
  <article
    v-if="!dismissed"
    class="text-orange-600 p-4 bg-white rounded-md flex items-center justify-center
    gap-3 fixed z-[10000] left-6 shadow-lg cursor-pointer"
    title="Click to dismiss"
    @click="dismissed = true"
  >
    <WifiOff :size="20" />
    <span class="text-gray-800">You’re offline.</span>
  </article>
</template>

<script lang="ts" setup>
import { WifiOff } from 'lucide-vue-next'
import { ref } from 'vue'

const dismissed = ref(false)
</script>

<style lang="postcss" scoped>
article {
  bottom: calc(var(--footer-height) + 1.2rem);
}
</style>
