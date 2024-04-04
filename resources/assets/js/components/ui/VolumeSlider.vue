<template>
  <span id="volume" class="volume" :class="level">
    <span
      v-show="level === 'muted'"
      v-koel-tooltip.top
      role="button"
      tabindex="0"
      title="Unmute"
      @click="unmute"
    >
      <Icon :icon="faVolumeMute" fixed-width />
    </span>

    <span
      v-show="level !== 'muted'"
      v-koel-tooltip.top
      role="button"
      tabindex="0"
      title="Mute"
      @click="mute"
    >
      <Icon :icon="level === 'discreet' ? faVolumeLow : faVolumeHigh" fixed-width />
    </span>

    <input
      ref="inputEl"
      class="plyr__volume"
      max="10"
      role="slider"
      step="0.1"
      title="Volume"
      type="range"
      @input="setVolume"
    >
  </span>
</template>

<script lang="ts" setup>
import { faVolumeHigh, faVolumeLow, faVolumeMute } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted, ref } from 'vue'
import { socketService, volumeManager } from '@/services'
import { preferenceStore } from '@/stores'
import { watchThrottled } from '@vueuse/core'

const inputEl = ref<HTMLInputElement>()

const level = computed(() => {
  if (volumeManager.volume.value === 0) return 'muted'
  if (volumeManager.volume.value < 3) return 'discreet'
  return 'loud'
})

const mute = () => volumeManager.mute()
const unmute = () => volumeManager.unmute()
const setVolume = (e: Event) => volumeManager.set(parseFloat((e.target as HTMLInputElement).value))

// since changing volume can be frequent, we throttle the event to avoid too many "save preferences" API calls
// and socket broadcasts
watchThrottled(volumeManager.volume, volume => {
  preferenceStore.volume = volume
  socketService.broadcast('SOCKET_VOLUME_CHANGED', volume)
}, { throttle: 1_000 })

onMounted(() => volumeManager.init(inputEl.value!, preferenceStore.volume))
</script>

<style lang="postcss" scoped>
#volume {
  position: relative;
  display: flex;
  align-items: center;

  [type=range] {
    margin: 0 0 0 8px;
    width: 120px;
    height: 4px;
    border-radius: 4px;
    position: relative;

    /* increase click area */
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
