<template>
  <span id="volume" class="volume" :class="level">
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
  display: flex;
  align-items: center;
  justify-content: center;

  [type=range] {
    margin: 0 0 0 8px;
    width: 120px;
    height: 4px;
    border-radius: 4px;
    position: relative;

    // increase click area
    &::before {
      position: absolute;
      content: ' ';
      left: 0;
      right: 0;
      top: -12px;
      bottom: -12px;
    }

    &::-webkit-slider-thumb {
      background: var(--color-text-secondary);
    }

    &:hover {
      &::-webkit-slider-thumb {
        background: var(--color-text-primary);
      }
    }
  }

  &.muted {
    [type=range] {
      &::-webkit-slider-thumb {
        background: transparent;
        box-shadow: none;
      }
    }
  }

  @media only screen and (max-width: 768px) {
    display: none !important;
  }
}
</style>
