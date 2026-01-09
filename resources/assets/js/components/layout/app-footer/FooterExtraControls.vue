<template>
  <div class="extra-controls flex justify-end relative md:w-[420px] px-6 md:px-8 py-0">
    <div class="flex justify-end items-center gap-6">
      <FooterQueueIcon />

      <FooterBtn
        class="visualizer-btn hidden md:!block"
        data-testid="toggle-visualizer-btn"
        :title="t('ui.tooltips.toggleVisualizer')"
        @click.prevent="toggleVisualizer"
      >
        <Icon :icon="faBolt" fixed-width />
      </FooterBtn>

      <FooterBtn
        v-if="useEqualizer"
        :class="{ active: showEqualizer, 'pointer-events-none opacity-30 cursor-not-allowed': isRadio }"
        class="equalizer"
        :title="isRadio ? t('ui.tooltips.equalizerNotAvailableForRadio') : t('ui.tooltips.showEqualizer')"
        @click.prevent="!isRadio && showEqualizer()"
      >
        <AudioLinesIcon :size="16" />
      </FooterBtn>

      <VolumeSlider />

      <FooterBtn v-if="isFullscreenSupported()" :title="fullscreenButtonTitle" @click.prevent="toggleFullscreen">
        <Icon :icon="isFullscreen ? faCompress : faExpand" fixed-width />
      </FooterBtn>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { faBolt, faCompress, faExpand } from '@fortawesome/free-solid-svg-icons'
import { AudioLinesIcon } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { eventBus } from '@/utils/eventBus'
import { isFullscreenSupported, isAudioContextSupported as useEqualizer } from '@/utils/supports'
import { useRouter } from '@/composables/useRouter'
import { requireInjection } from '@/utils/helpers'
import { CurrentStreamableKey } from '@/symbols'
import { isRadioStation } from '@/utils/typeGuards'

import VolumeSlider from '@/components/ui/VolumeSlider.vue'
import FooterBtn from '@/components/layout/app-footer/FooterButton.vue'
import FooterQueueIcon from '@/components/layout/app-footer/FooterQueueButton.vue'

const { t } = useI18n()

const streamable = requireInjection(CurrentStreamableKey, ref())
const isRadio = computed(() => streamable.value && isRadioStation(streamable.value))

const isFullscreen = ref(false)
const fullscreenButtonTitle = computed(() => (isFullscreen.value ? t('ui.tooltips.exitFullscreen') : t('ui.tooltips.enterFullscreen')))

const { go, isCurrentScreen, url } = useRouter()

const showEqualizer = () => eventBus.emit('MODAL_SHOW_EQUALIZER')
const toggleFullscreen = () => eventBus.emit('FULLSCREEN_TOGGLE')
const toggleVisualizer = () => go(isCurrentScreen('Visualizer') ? -1 : url('visualizer'))

onMounted(() => {
  document.addEventListener('fullscreenchange', () => {
    isFullscreen.value = Boolean(document.fullscreenElement)
  })
})
</script>

<style lang="postcss" scoped>
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
