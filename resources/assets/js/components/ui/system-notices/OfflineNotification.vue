<template>
  <slot />
</template>

<script lang="ts" setup>
import { WifiOffIcon } from 'lucide-vue-next'
import { onBeforeUnmount, onMounted } from 'vue'
import { useMessageToaster } from '@/composables/useMessageToaster'

let dismissToast: ReturnType<typeof useMessageToaster>['dismissToast']
let toastId: string

onMounted(() => {
  const toaster = useMessageToaster()
  dismissToast = toaster.dismissToast
  toastId = toaster.toastWarning({ content: "You're offline.", icon: WifiOffIcon, persistent: true })
})

onBeforeUnmount(() => dismissToast(toastId))
</script>
