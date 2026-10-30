<template>
  <slot />
</template>

<script lang="ts" setup>
import { RocketIcon } from 'lucide-vue-next'
import { onBeforeUnmount, onMounted } from 'vue'
import { useEventListener } from '@vueuse/core'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { forceReloadWindow } from '@/utils/helpers'
import { isNewerVersionDeployed } from '@/utils/deployment'
import { eventBus } from '@/utils/eventBus'

let toastInfo: ReturnType<typeof useMessageToaster>['toastInfo']
let shown = false

const showNotice = () => {
  if (shown) {
    return
  }

  shown = true

  toastInfo({
    content: 'Koel has been updated.',
    icon: RocketIcon,
    actions: [{ label: 'Reload', handler: forceReloadWindow, primary: true }],
    onDismiss: () => (shown = false),
  })
}

onMounted(() => {
  toastInfo = useMessageToaster().toastInfo
  eventBus.on('NEW_VERSION_DEPLOYED', showNotice)
})

onBeforeUnmount(() => eventBus.off('NEW_VERSION_DEPLOYED', showNotice))

useEventListener(window, 'vite:preloadError', async () => {
  if (await isNewerVersionDeployed()) {
    showNotice()
  }
})
</script>
