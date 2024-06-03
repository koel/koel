<template>
  <span class="volume">
    <OnClickOutside @trigger="closeVolumeSlider">
      <span v-show="showingVolumeSlider" id="volumeSlider" ref="volumeSlider" />
    </OnClickOutside>
    <span class="icon" @click.stop="toggleVolumeSlider">
      <Icon :icon="muted ? faVolumeMute : faVolumeHigh" fixed-width />
    </span>
  </span>
</template>

<script lang="ts" setup>
import { faVolumeHigh, faVolumeMute } from '@fortawesome/free-solid-svg-icons'
import noUISlider from 'nouislider'
import { OnClickOutside } from '@vueuse/components'
import { inject, onMounted, ref, watch } from 'vue'
import { socketService } from '@/services'
import { RemoteState } from '@/remote/types'

const DEFAULT_VOLUME = 7

const volumeSlider = ref<EqualizerBandElement>()
const muted = ref(false)
const showingVolumeSlider = ref(false)

const toggleVolumeSlider = () => (showingVolumeSlider.value = !showingVolumeSlider.value)
const closeVolumeSlider = () => (showingVolumeSlider.value = false)

const state = inject<RemoteState>('state')

watch(() => state?.volume, volume => volumeSlider.value?.noUiSlider?.set(volume || DEFAULT_VOLUME))

onMounted(() => {
  noUISlider.create(volumeSlider.value!, {
    orientation: 'vertical',
    connect: [true, false],
    start: state?.volume || DEFAULT_VOLUME,
    range: { min: 0, max: 10 },
    direction: 'rtl'
  })

  if (!volumeSlider.value?.noUiSlider) {
    throw new Error('Failed to initialize noUISlider on element #volumeSlider')
  }

  volumeSlider.value!.noUiSlider.on('change', (values: string[], handle: number) => {
    const volume = values[handle]
    muted.value = !volume
    socketService.broadcast('SOCKET_SET_VOLUME', volume)
  })
})

</script>


<style lang="postcss">
.volume {
  position: relative;

  .icon {
    width: 20px;
    display: inline-block;
    text-align: center;
  }

  .noUi-target {
    background: var(--color-text-primary);
    border-radius: 4px;
    border: 0;
    box-shadow: none;
    left: 7px;
  }

  .noUi-base {
    height: calc(100% - 16px);
    border-radius: 4px;
  }

  .noUi-vertical {
    width: 8px;
  }

  .noUi-vertical .noUi-handle {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 0;
    left: -12px;
    top: 0;
    background: var(--color-highlight);
    box-shadow: none;

    &::after, &::before {
      display: none;
    }
  }

  .noUi-connect {
    background: transparent;
    box-shadow: none;
  }
}

#volumeSlider {
  height: 80px;
  position: absolute;
  bottom: calc(50% + 26px);
}
</style>
