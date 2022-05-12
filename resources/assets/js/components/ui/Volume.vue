<template>
  <span id="volume" class="volume control">
    <i v-if="muted" class="fa fa-volume-off" role="button" tabindex="0" title="Unmute" @click="unmute"/>
    <i v-else class="fa fa-volume-up" role="button" tabindex="0" title="Mute" @click="mute"/>
    <input
      id="volumeInput"
      class="plyr__volume"
      max="10"
      role="slider"
      step="0.1"
      title="Volume"
      type="range"
      @change="broadcastVolume"
      @input="setVolume"
    >
  </span>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { playbackService, socketService } from '@/services'

const muted = ref(false)

const mute = () => {
  muted.value = true
  playbackService.mute()
}

const unmute = () => {
  muted.value = false
  playbackService.unmute()
}

const setVolume = (e: InputEvent) => {
  const volume = parseFloat((e.target as HTMLInputElement).value)
  playbackService.setVolume(volume)
  muted.value = volume === 0
}

/**
 * Broadcast the volume changed event to remote controller.
 */
const broadcastVolume = (e: InputEvent) => {
  socketService.broadcast('SOCKET_VOLUME_CHANGED', parseFloat((e.target as HTMLInputElement).value))
}
</script>

<style lang="scss">
#volume {
  position: relative;
  z-index: 99;

  // More tweaks
  [type=range] {
    margin: -1px 0 0 5px;
    transform: rotate(270deg);
    transform-origin: 0;
    position: absolute;
    bottom: -25px;
    border: 14px solid var(--color-bg-primary);
    border-left-width: 30px;
    z-index: 0;
    width: 140px;
    border-radius: 4px;
    display: none;
  }

  &:hover [type=range] {
    display: block;
  }

  i {
    width: 16px;
    position: relative;
    z-index: 1;
  }

  @media only screen and (max-width: 768px) {
    display: none !important;
  }
}
</style>
