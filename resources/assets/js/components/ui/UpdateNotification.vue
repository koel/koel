<template>
  <article
    v-if="newerVersionDeployed"
    data-testid="update-notification"
    class="fixed z-10000 left-4 flex items-center gap-3 py-3 pl-4 pr-3 rounded-xl border border-k-fg-10 bg-k-bg-context-menu text-k-fg shadow-lg"
  >
    <RocketIcon :size="18" class="shrink-0 text-k-fg-70" />
    <span class="whitespace-nowrap">Koel has been updated.</span>
    <Btn size="small" variant="highlight" @click="forceReloadWindow">Reload</Btn>
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
  bottom: calc(var(--footer-height) + 2rem);
}
</style>
