<template>
  <article
    v-if="newerVersionDeployed"
    data-testid="update-notification"
    class="text-k-primary p-4 bg-white rounded-md flex items-center gap-3 fixed z-10000 left-6 shadow-lg"
  >
    <RocketIcon :size="20" class="shrink-0" />
    <span class="text-gray-800 whitespace-nowrap">Koel has been updated.</span>
    <Btn size="small" @click="forceReloadWindow">Reload</Btn>
  </article>
</template>

<script lang="ts" setup>
import { RocketIcon } from 'lucide-vue-next'
import { onBeforeUnmount, ref } from 'vue'
import { useEventListener } from '@vueuse/core'
import { forceReloadWindow } from '@/utils/helpers'
import { isNewerVersionDeployed } from '@/utils/deployment'
import { eventBus } from '@/utils/eventBus'

import Btn from '@/components/ui/form/Btn.vue'

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

<style lang="postcss" scoped>
article {
  bottom: calc(var(--footer-height) + 1.2rem);
}
</style>
