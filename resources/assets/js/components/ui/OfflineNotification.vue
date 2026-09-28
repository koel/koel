<template>
  <article
    v-if="!dismissed"
    class="fixed z-10000 left-4 flex items-center gap-3 max-w-xs py-3 px-4 rounded-xl border border-k-fg-10 bg-k-bg-context-menu text-k-fg shadow-lg cursor-pointer"
    title="Click to dismiss"
    @click="dismissed = true"
  >
    <WifiOff :size="18" class="shrink-0 text-k-warning" />
    <span>You're offline.</span>
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
  bottom: calc(var(--footer-height) + 2rem);
}
</style>
