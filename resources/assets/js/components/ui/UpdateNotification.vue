<template>
  <article
    v-if="newerVersionDeployed"
    class="text-k-primary p-4 bg-white rounded-md flex items-center gap-3 fixed z-10000 left-6 shadow-lg"
  >
    <CircleFadingArrowUpIcon :size="20" class="shrink-0" />
    <span class="text-gray-800 whitespace-nowrap">Koel has been updated.</span>
    <Btn size="small" @click="forceReloadWindow">Reload</Btn>
  </article>
</template>

<script lang="ts" setup>
import { CircleFadingArrowUpIcon } from 'lucide-vue-next'
import { ref } from 'vue'
import { useEventListener } from '@vueuse/core'
import { forceReloadWindow } from '@/utils/helpers'
import { isNewerVersionDeployed } from '@/utils/deployment'

import Btn from '@/components/ui/form/Btn.vue'

const newerVersionDeployed = ref(false)

useEventListener(window, 'vite:preloadError', async () => {
  newerVersionDeployed.value ||= await isNewerVersionDeployed()
})
</script>

<style lang="postcss" scoped>
article {
  bottom: calc(var(--footer-height) + 1.2rem);
}
</style>
