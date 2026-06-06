<template>
  <span id="volume" :class="level" class="hidden md:flex relative items-center gap-2">
    <FooterExtraControlBtn v-show="level === 'muted'" tabindex="0" title="Unmute" @click="unmute">
      <Icon :icon="faVolumeMute" fixed-width />
    </FooterExtraControlBtn>

    <FooterExtraControlBtn v-show="level !== 'muted'" tabindex="0" title="Mute" @click="mute">
      <Icon :icon="level === 'discreet' ? faVolumeLow : faVolumeHigh" fixed-width />
    </FooterExtraControlBtn>

    <input
      ref="inputEl"
      class="volume-slider w-[120px]! before:absolute before:left-0 before:right-0 before:top-[-12px] before:bottom-[-12px]"
      max="10"
      role="slider"
      step="0.1"
      title="Volume"
      type="range"
      @input="setVolume"
    />
  </span>
</template>

<script lang="ts" setup>
import { faVolumeHigh, faVolumeLow, faVolumeMute } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted, ref } from 'vue'
import { watchThrottled } from '@vueuse/core'
import { socketService } from '@/services/socketService'
import { volumeManager } from '@/services/volumeManager'
import { preferenceStore } from '@/stores/preferenceStore'

import FooterExtraControlBtn from '@/components/layout/app-footer/FooterButton.vue'

const inputEl = ref<HTMLInputElement>()

const level = computed(() => {
  if (volumeManager.volume.value === 0) {
    return 'muted'
  }
  if (volumeManager.volume.value < 3) {
    return 'discreet'
  }
  return 'loud'
})

const mute = () => volumeManager.mute()
const unmute = () => volumeManager.unmute()
const setVolume = (e: Event) => volumeManager.set(Number.parseFloat((e.target as HTMLInputElement).value))

// since changing volume can be frequent, we throttle the event to avoid too many "save preferences" API calls
// and socket broadcasts
watchThrottled(
  volumeManager.volume,
  volume => {
    preferenceStore.volume = volume
    socketService.broadcast('SOCKET_VOLUME_CHANGED', volume)
  },
  { throttle: 1_000 },
)

onMounted(() => volumeManager.init(inputEl.value!, preferenceStore.volume))
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.volume-slider[type='range'] {
  -webkit-appearance: none;
  -moz-appearance: none;
  vertical-align: middle;
  padding: 0;
  cursor: pointer;
  background: transparent;
  border: none;
}

.volume-slider[type='range']:focus {
  outline: 0;
}

.volume-slider[type='range']::-webkit-slider-runnable-track {
  height: 6px;
  border: 0;
  border-radius: 3px;
  @apply bg-k-fg-10;
}

.volume-slider[type='range']::-webkit-slider-thumb {
  -webkit-appearance: none;
  margin-top: -3px;
  height: 12px;
  width: 12px;
  border: 0;
  border-radius: 100%;
  transition: background 0.3s ease;
  cursor: ew-resize;
  @apply bg-k-fg-70;
}

.volume-slider[type='range']::-moz-range-track {
  height: 6px;
  border: 0;
  border-radius: 3px;
  @apply bg-k-fg-10;
}

.volume-slider[type='range']::-moz-range-thumb {
  height: 12px;
  width: 12px;
  border: 0;
  border-radius: 100%;
  transition: background 0.3s ease;
  cursor: ew-resize;
  @apply bg-k-fg-70;
}

#volume {
  [type='range']:hover::-webkit-slider-thumb {
    @apply bg-k-fg;
  }

  [type='range']:hover::-moz-range-thumb {
    @apply bg-k-fg;
  }

  &.muted [type='range'] {
    &::-webkit-slider-thumb {
      @apply bg-transparent shadow-none;
    }

    &::-moz-range-thumb {
      @apply bg-transparent shadow-none;
    }
  }
}
</style>
