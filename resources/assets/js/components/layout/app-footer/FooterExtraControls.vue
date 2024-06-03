<template>
  <div class="extra-controls flex justify-end relative md:w-[420px] px-6 md:px-8 py-0">
    <div class="flex justify-end items-center gap-6">
      <FooterQueueIcon />

      <FooterBtn
        class="visualizer-btn hidden md:!block"
        data-testid="toggle-visualizer-btn"
        title="Toggle visualizer"
        @click.prevent="toggleVisualizer"
      >
        <Icon :icon="faBolt" fixed-width />
      </FooterBtn>

      <FooterBtn
        v-if="useEqualizer"
        :class="{ active: showEqualizer }"
        class="equalizer"
        title="Show equalizer"
        @click.prevent="showEqualizer"
      >
        <Icon :icon="faSliders" fixed-width />
      </FooterBtn>

      <VolumeSlider />

      <FooterBtn v-if="isFullscreenSupported()" :title="fullscreenButtonTitle" @click.prevent="toggleFullscreen">
        <Icon :icon="isFullscreen ? faCompress : faExpand" fixed-width />
      </FooterBtn>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { faBolt, faCompress, faExpand, faSliders } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted, ref } from 'vue'
import { eventBus, isAudioContextSupported as useEqualizer, isFullscreenSupported } from '@/utils'
import { useRouter } from '@/composables'

import VolumeSlider from '@/components/ui/VolumeSlider.vue'
import FooterBtn from '@/components/layout/app-footer/FooterButton.vue'
import FooterQueueIcon from '@/components/layout/app-footer/FooterQueueButton.vue'

const isFullscreen = ref(false)
const fullscreenButtonTitle = computed(() => (isFullscreen.value ? 'Exit fullscreen mode' : 'Enter fullscreen mode'))

const { go, isCurrentScreen } = useRouter()

const showEqualizer = () => eventBus.emit('MODAL_SHOW_EQUALIZER')
const toggleFullscreen = () => eventBus.emit('FULLSCREEN_TOGGLE')
const toggleVisualizer = () => go(isCurrentScreen('Visualizer') ? -1 : 'visualizer')

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
