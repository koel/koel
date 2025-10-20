<template>
  <a
    class="bg-k-bg-50 active:scale-90 border border-k-fg-10 flex items-center px-3.5 rounded-md text-k-fg-70 hover:text-k-fg"
    :href="url('home')"
    @click="onClick"
  >
    <Icon :icon="faHome" fixed-width />
  </a>
</template>

<script setup lang="ts">
import { faHome } from '@fortawesome/free-solid-svg-icons'
import { eventBus } from '@/utils/eventBus'
import { useRouter } from '@/composables/useRouter'

const { url } = useRouter()
const onClick = () => eventBus.emit('TOGGLE_SIDEBAR')
</script>
