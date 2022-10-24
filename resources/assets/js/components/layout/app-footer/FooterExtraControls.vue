<template>
  <div class="extra-controls" data-testid="other-controls">
    <div v-koel-clickaway="closeEqualizer" class="wrapper">
      <Equalizer v-if="useEqualizer" v-show="showEqualizer"/>

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
        :title="`${ showEqualizer ? 'Hide' : 'Show'} equalizer`"
        class="equalizer"
        data-testid="toggle-equalizer-btn"
        type="button"
        @click.prevent="toggleEqualizer"
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

import Equalizer from '@/components/ui/Equalizer.vue'
import Volume from '@/components/ui/Volume.vue'

const song = requireInjection(CurrentSongKey, ref(null))

const showEqualizer = ref(false)

const toggleEqualizer = () => (showEqualizer.value = !showEqualizer.value)
const closeEqualizer = () => (showEqualizer.value = false)
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
