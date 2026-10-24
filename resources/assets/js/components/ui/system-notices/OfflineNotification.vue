<template>
  <SystemFloatingNotice v-if="!dismissed" class="items-center max-w-xs cursor-pointer" @click="dismissed = true">
    <template #icon>
      <WifiOffIcon :size="18" class="text-k-warning" />
    </template>
    <span>You're offline.</span>
  </SystemFloatingNotice>
</template>

<script lang="ts" setup>
import { WifiOffIcon } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { useNetworkStatus } from '@/composables/useNetworkStatus'

import SystemFloatingNotice from '@/components/ui/system-notices/SystemFloatingNotice.vue'

const { online } = useNetworkStatus()
const dismissed = ref(false)

// Re-show the notification each time we go offline
watch(online, isOnline => {
  if (!isOnline) {
    dismissed.value = false
  }
})
</script>
