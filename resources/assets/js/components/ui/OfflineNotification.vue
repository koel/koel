<template>
  <article
    v-if="!dismissed"
    class="text-orange-600 p-4 bg-white rounded-md flex items-center gap-3 fixed z-10000 left-6 shadow-lg cursor-pointer max-w-xs"
    title="Click to dismiss"
    @click="dismissed = true"
  >
    <WifiOff :size="20" class="shrink-0" />
    <span class="text-gray-800">You're offline.</span>
  </article>
</template>

<script lang="ts" setup>
import { WifiOff } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { useNetworkStatus } from '@/composables/useNetworkStatus'

const { online } = useNetworkStatus()
const dismissed = ref(false)

// Re-show the notification each time we go offline
watch(online, isOnline => {
  if (!isOnline) {
    dismissed.value = false
  }
})
</script>

<style lang="postcss" scoped>
article {
  bottom: calc(var(--footer-height) + 1.2rem);
}
</style>
