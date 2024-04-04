<template>
  <div class="flex flex-col items-center justify-center flex-1 relative h-screen gap-4 text-k-text-secondary">
    <template v-if="!maxRetriesReached">
      <span class="relative flex h-5 aspect-square">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-k-highlight opacity-75" />
        <span class="relative inline-flex rounded-full h-5 aspect-square bg-k-highlight opacity-70" />
      </span>
      <p>Scanning for an active Koel instance…</p>
    </template>
    <p v-else>
      No active Koel instance found.
      <a class="text-k-highlight ml-1" @click.prevent="rescan">Rescan</a>
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { socketService } from '@/services'

const MAX_RETRIES = 10
const connected = ref(false)
const retries = ref(0)

const maxRetriesReached = computed(() => retries.value >= MAX_RETRIES)

const getStatus = () => socketService.broadcast('SOCKET_GET_STATUS')

const scan = () => {
  if (!connected.value) {
    if (!maxRetriesReached.value) {
      getStatus()
      retries.value++
      window.setTimeout(scan, 1000)
    }
  } else {
    retries.value = 0
  }
}

const rescan = () => {
  retries.value = 0
  scan()
}

onMounted(() => {
  scan()
})
</script>
