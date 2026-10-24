<template>
  <FloatingNotice v-if="!dismissed" class="items-center max-w-xs cursor-pointer" @click="dismissed = true">
    <WifiOff :size="18" class="shrink-0 text-k-warning" />
    <span>You're offline.</span>
  </FloatingNotice>
</template>

<script lang="ts" setup>
import { WifiOff } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { useNetworkStatus } from '@/composables/useNetworkStatus'

import FloatingNotice from '@/components/ui/FloatingNotice.vue'

const { online } = useNetworkStatus()
const dismissed = ref(false)

// Re-show the notification each time we go offline
watch(online, isOnline => {
  if (!isOnline) {
    dismissed.value = false
  }
})
</script>
