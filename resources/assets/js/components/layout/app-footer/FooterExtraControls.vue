<template>
  <div class="extra-controls" data-testid="other-controls">
    <div class="wrapper">
      <button
        v-if="song?.playback_state === 'Playing'"
        v-koel-tooltip.top
        class="visualizer-btn"
        data-testid="toggle-visualizer-btn"
        title="Toggle the visualizer"
        type="button"
        @click.prevent="toggleVisualizer"
      >
        <icon :icon="faBolt"/>
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
        <icon :icon="faSliders"/>
      </button>

      <Volume/>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { faBolt, faSliders } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { eventBus, isAudioContextSupported as useEqualizer, requireInjection } from '@/utils'
import { CurrentSongKey } from '@/symbols'

import Volume from '@/components/ui/Volume.vue'

const song = requireInjection(CurrentSongKey, ref(null))

const showEqualizer = () => eventBus.emit('MODAL_SHOW_EQUALIZER')
const toggleVisualizer = () => eventBus.emit('TOGGLE_VISUALIZER')
</script>

<style lang="scss" scoped>
.extra-controls {
  display: flex;
  justify-content: flex-end;
  position: relative;
  width: 320px;
  color: var(--color-text-secondary);
  padding: 0 2rem;

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
}
</style>
