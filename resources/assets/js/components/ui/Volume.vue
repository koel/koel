<template>
  <span id="volume" class="volume control">
    <icon
      v-if="level === 'muted'"
      :icon="faVolumeMute"
      fixed-width
      role="button"
      tabindex="0"
      title="Unmute"
      @click="unmute"
    />
    <icon
      v-else
      :icon="level === 'discreet' ? faVolumeLow : faVolumeHigh"
      fixed-width
      role="button"
      tabindex="0"
      title="Mute"
      @click="mute"
    />

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
import { faVolumeHigh, faVolumeLow, faVolumeMute } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { playbackService, socketService } from '@/services'
import { preferenceStore as preferences } from '@/stores'
import { eventBus } from '@/utils'

const level = ref<'muted' | 'discreet' | 'loud'>()

const mute = () => {
  playbackService.mute()
  level.value = 'muted'
}

const unmute = () => {
  playbackService.unmute()
  level.value = preferences.volume < 3 ? 'discreet' : 'loud'
}

const setVolume = (e: InputEvent) => {
  const volume = parseFloat((e.target as HTMLInputElement).value)
  playbackService.setVolume(volume)
  setLevel(volume)
}

const setLevel = (volume: number) => (level.value = volume === 0 ? 'muted' : volume < 3 ? 'discreet' : 'loud')

/**
 * Broadcast the volume changed event to remote controller.
 */
const broadcastVolume = (e: InputEvent) => {
  socketService.broadcast('SOCKET_VOLUME_CHANGED', parseFloat((e.target as HTMLInputElement).value))
}

eventBus.on('KOEL_READY', () => setLevel(preferences.volume))
</script>

<style lang="scss">
#volume {
  position: relative;
  z-index: 99;

  // More tweaks
  [type=range] {
    margin: 0 0 0 8px;
    transform: rotate(270deg);
    transform-origin: 0;
    position: absolute;
    bottom: -22px;
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

  [role=button] {
    position: relative;
    z-index: 1;
  }

  @media only screen and (max-width: 768px) {
    display: none !important;
  }
}
</style>
