<template>
  <FooterBtn v-if="isTargetAvailable" :class="{ wireless: isPlayingWirelessly }" @click.prevent="showTargetPicker">
    <AirplayIcon :size="16" />
    <span class="sr-only">{{ isPlayingWirelessly ? 'Playing on AirPlay' : 'AirPlay' }}</span>
  </FooterBtn>
</template>

<script lang="ts" setup>
import { AirplayIcon } from 'lucide-vue-next'
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { getActiveMedia } from '@/services/playbackManager'

import FooterBtn from '@/components/layout/app-footer/FooterButton.vue'

const isTargetAvailable = ref(false)
const isPlayingWirelessly = ref(false)

let audioElement: HTMLMediaElement | null = null
let pickerMedia: HTMLMediaElement | null = null

const updateTargetAvailability = (event: Event) => {
  isTargetAvailable.value = (event as Event & { availability: string }).availability === 'available'
}

const updateWirelessState = () => {
  isPlayingWirelessly.value = Boolean(pickerMedia?.webkitCurrentPlaybackTargetIsWireless)
}

const watchWirelessState = (media: HTMLMediaElement | null) => {
  pickerMedia?.removeEventListener('webkitcurrentplaybacktargetiswirelesschanged', updateWirelessState)
  pickerMedia = media
  pickerMedia?.addEventListener('webkitcurrentplaybacktargetiswirelesschanged', updateWirelessState)
}

const showTargetPicker = () => {
  const media = getActiveMedia()

  if (media !== pickerMedia) {
    watchWirelessState(media)
  }

  media?.webkitShowPlaybackTargetPicker?.()
}

onMounted(() => {
  audioElement = document.querySelector<HTMLMediaElement>('#audio-player')
  audioElement?.addEventListener('webkitplaybacktargetavailabilitychanged', updateTargetAvailability)
})

onBeforeUnmount(() => {
  audioElement?.removeEventListener('webkitplaybacktargetavailabilitychanged', updateTargetAvailability)
  watchWirelessState(null)
})
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.wireless {
  @apply text-k-highlight;
}
</style>
