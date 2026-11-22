<template>
  <div class="extra-controls flex justify-end relative md:w-[420px] px-6 md:px-8 py-0">
    <div class="flex justify-end items-center gap-6">
      <FooterQueueIcon />

      <FooterBtn
        class="visualizer-btn hidden md:block!"
        data-testid="toggle-visualizer-btn"
        @click.prevent="toggleVisualizer"
      >
        <Icon :icon="faBolt" fixed-width />
        <span class="sr-only">Toggle visualizer</span>
      </FooterBtn>

      <FooterBtn
        v-if="useEqualizer"
        :class="{ active: showEqualizer }"
        class="equalizer"
        @click.prevent="showEqualizer"
      >
        <AudioLinesIcon :size="16" />
        <span class="sr-only">Show equalizer</span>
      </FooterBtn>

      <VolumeSlider />

      <FooterAirPlayButton v-if="isAirPlaySupported()" />

      <FooterPlaybackSpeedButton v-if="playable" :playable class="hidden md:block!" />

      <FooterMiniPlayerButton v-if="isMiniPlayerSupported()" class="hidden md:block!" />

      <FooterBtn v-if="isFullscreenSupported()" class="hidden md:block!" @click.prevent="toggleFullscreen">
        <Icon :icon="isFullscreen ? faCompress : faExpand" fixed-width />
        <span class="sr-only">{{ fullscreenButtonLabel }}</span>
      </FooterBtn>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { faBolt, faCompress, faExpand } from '@fortawesome/free-solid-svg-icons'
import { AudioLinesIcon } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import { eventBus } from '@/utils/eventBus'
import {
  isAirPlaySupported,
  isFullscreenSupported,
  isMiniPlayerSupported,
  isAudioContextSupported as useEqualizer,
} from '@/utils/supports'
import { isRadioStation } from '@/utils/typeGuards'
import { CurrentStreamableKey } from '@/config/symbols'
import { defineAsyncComponent, requireInjection } from '@/utils/helpers'
import { useRouter } from '@/composables/useRouter'
import { useModal } from '@/composables/useModal'

import VolumeSlider from '@/components/ui/VolumeSlider.vue'
import FooterBtn from '@/components/layout/app-footer/FooterButton.vue'
import FooterQueueIcon from '@/components/layout/app-footer/FooterQueueButton.vue'
import FooterAirPlayButton from '@/components/layout/app-footer/FooterAirPlayButton.vue'
import FooterPlaybackSpeedButton from '@/components/layout/app-footer/FooterPlaybackSpeedButton.vue'
import FooterMiniPlayerButton from '@/components/layout/app-footer/FooterMiniPlayerButton.vue'

const Equalizer = defineAsyncComponent(() => import('@/components/ui/equalizer/Equalizer.vue'))
const { openModal } = useModal()

const streamable = requireInjection(CurrentStreamableKey, ref())
const playable = computed(() => (streamable.value && !isRadioStation(streamable.value) ? streamable.value : null))

const isFullscreen = ref(false)
const fullscreenButtonLabel = computed(() => (isFullscreen.value ? 'Exit fullscreen mode' : 'Enter fullscreen mode'))

const { go, isCurrentScreen, url } = useRouter()

const showEqualizer = () => openModal<'EQUALIZER'>(Equalizer)
const toggleFullscreen = () => eventBus.emit('FULLSCREEN_TOGGLE')
const toggleVisualizer = () => go(isCurrentScreen('Visualizer') ? -1 : url('visualizer'))

onMounted(() => {
  document.addEventListener('fullscreenchange', () => {
    isFullscreen.value = Boolean(document.fullscreenElement)
  })
})
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.extra-controls {
  :fullscreen & {
    @apply pr-0;
  }

  :fullscreen & {
    .visualizer-btn {
      @apply hidden;
    }
  }
}
</style>
