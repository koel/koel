<template>
  <a
    :href="url('home')"
    :title="name"
    class="flex items-center justify-center px-2.5 rounded-md border border-k-fg-10 bg-k-bg-50 active:scale-90"
    @click="onClick"
  >
    <img :src="logo" :alt="name" class="size-6 object-contain" />
  </a>
</template>

<script setup lang="ts">
import { eventBus } from '@/utils/eventBus'
import { useBranding } from '@/composables/useBranding'
import { useRouter } from '@/composables/useRouter'

const { url } = useRouter()
const { logo, name } = useBranding()

const onClick = () => eventBus.emit('TOGGLE_SIDEBAR')
</script>
