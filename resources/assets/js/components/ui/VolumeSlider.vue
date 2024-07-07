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
      class="plyr__volume !w-[120px] before:absolute before:left-0 before:right-0 before:top-[-12px] before:bottom-[-12px]"
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
import FooterExtraControlBtn from '@/components/layout/app-footer/FooterButton.vue'

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
  [type=range] {
    &::-webkit-slider-thumb {
      @apply bg-k-text-secondary;
    }

    &:hover {
      &::-webkit-slider-thumb {
        @apply bg-k-text-primary;
      }
    }
  }

  &.muted {
    [type=range] {
      &::-webkit-slider-thumb {
        @apply bg-transparent shadow-none;
      }
    }
  }
}
</style>
