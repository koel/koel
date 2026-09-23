<template>
  <div class="h-full w-full flex items-center justify-center text-k-fg-50">
    <div v-if="cause === 'offline'" class="flex flex-col items-center gap-4">
      <WifiOffIcon :size="64" />
      <span class="text-3xl font-light">You're offline.</span>
    </div>
    <div v-else-if="cause === 'outdated'" class="flex flex-col items-center gap-4">
      <RefreshCwIcon :size="64" />
      <span class="text-3xl font-light">Koel has been updated.</span>
      <Btn @click="forceReloadWindow">Reload</Btn>
    </div>
    <div v-else-if="cause === 'unknown'" class="flex flex-col items-center gap-4">
      <CircleAlertIcon :size="64" />
      <span class="text-3xl font-light">Couldn't load this part of Koel.</span>
      <Btn @click="forceReloadWindow">Reload</Btn>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { CircleAlertIcon, RefreshCwIcon, WifiOffIcon } from 'lucide-vue-next'
import { onMounted, ref } from 'vue'
import { detectLoadFailureCause, type LoadFailureCause } from '@/utils/loadFailure'
import { forceReloadWindow } from '@/utils/helpers'

import Btn from '@/components/ui/form/Btn.vue'

const cause = ref<LoadFailureCause | null>(null)

onMounted(async () => {
  cause.value = await detectLoadFailureCause()
})
</script>
