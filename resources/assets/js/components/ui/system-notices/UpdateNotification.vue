<template>
  <SystemFloatingNotice v-if="newerVersionDeployed" class="items-center" data-testid="update-notification">
    <template #icon>
      <RocketIcon :size="18" class="text-k-fg-70" />
    </template>
    <span class="whitespace-nowrap">Koel has been updated.</span>
    <Btn size="small" variant="highlight" @click="forceReloadWindow">Reload</Btn>
  </SystemFloatingNotice>
</template>

<script lang="ts" setup>
import { RocketIcon } from 'lucide-vue-next'
import { onBeforeUnmount, ref } from 'vue'
import { useEventListener } from '@vueuse/core'
import { forceReloadWindow } from '@/utils/helpers'
import { isNewerVersionDeployed } from '@/utils/deployment'
import { eventBus } from '@/utils/eventBus'

import Btn from '@/components/ui/form/Btn.vue'
import SystemFloatingNotice from '@/components/ui/system-notices/SystemFloatingNotice.vue'

const newerVersionDeployed = ref(false)

const showNotice = () => {
  newerVersionDeployed.value = true
}

eventBus.on('NEW_VERSION_DEPLOYED', showNotice)
onBeforeUnmount(() => eventBus.off('NEW_VERSION_DEPLOYED', showNotice))

useEventListener(window, 'vite:preloadError', async () => {
  if (await isNewerVersionDeployed()) {
    showNotice()
  }
})
</script>
