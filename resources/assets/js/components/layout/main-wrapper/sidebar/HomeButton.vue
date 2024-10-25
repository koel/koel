<template>
  <a
    class="bg-black/20 flex items-center px-3.5 rounded-md !text-k-text-secondary hover:!text-k-text-primary"
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
