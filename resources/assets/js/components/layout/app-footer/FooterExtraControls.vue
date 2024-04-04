<template>
  <div class="extra-controls" data-testid="other-controls">
    <div class="wrapper">
      <button
        v-koel-tooltip.top
        class="visualizer-btn"
        data-testid="toggle-visualizer-btn"
        title="Toggle visualizer"
        @click.prevent="toggleVisualizer"
      >
        <Icon :icon="faBolt" />
      </button>

      <button
        v-if="useEqualizer"
        v-koel-tooltip.top
        :class="{ active: showEqualizer }"
        class="equalizer"
        title="Show equalizer"
        type="button"
        @click.prevent="showEqualizer"
      >
        <Icon :icon="faSliders" />
      </button>

      <VolumeSlider />

      <button
        v-if="isFullscreenSupported()"
        v-koel-tooltip.top
        :title="fullscreenButtonTitle"
        @click.prevent="toggleFullscreen"
      >
        <Icon :icon="isFullscreen ? faCompress : faExpand" />
      </button>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { faBolt, faCompress, faExpand, faSliders } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted, ref } from 'vue'
import { eventBus, isAudioContextSupported as useEqualizer, isFullscreenSupported } from '@/utils'
import { useRouter } from '@/composables'

import VolumeSlider from '@/components/ui/VolumeSlider.vue'

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
  display: flex;
  justify-content: flex-end;
  position: relative;
  width: 320px;
  color: var(--color-text-secondary);
  padding: 0 2rem;

  :fullscreen & {
    padding-right: 0;
  }

  .wrapper {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 1.5rem;
  }

  button {
    color: currentColor;
    transition: color 0.2s ease-in-out;

    &:hover {
      color: var(--color-text-primary);
    }
  }

  @media only screen and (max-width: 768px) {
    width: auto;

    .visualizer-btn {
      display: none;
    }
  }

  :fullscreen & {
    .visualizer-btn {
      display: none;
    }
  }
}
</style>
