<template>
  <div class="other-controls" data-testid="other-controls">
    <div v-koel-clickaway="closeEqualizer" class="wrapper">
      <Equalizer v-if="useEqualizer" v-show="showEqualizer"/>

      <button
        v-if="song?.playback_state === 'Playing'"
        class="control"
        data-testid="toggle-visualizer-btn"
        title="Show/hide the visualizer"
        type="button"
        @click.prevent="toggleVisualizer"
      >
        <icon :icon="faBolt"/>
      </button>

      <LikeButton v-if="song" :song="song" class="like"/>

      <button
        :class="{ active: showExtraPanel }"
        class="control text-uppercase"
        data-testid="toggle-extra-panel-btn"
        title="View song information"
        type="button"
        @click.prevent="toggleExtraPanel"
      >
        Info
      </button>

      <button
        v-if="useEqualizer"
        :class="{ active: showEqualizer }"
        :title="`${ showEqualizer ? 'Hide' : 'Show'} equalizer`"
        class="control equalizer"
        data-testid="toggle-equalizer-btn"
        type="button"
        @click.prevent="toggleEqualizer"
      >
        <icon :icon="faSliders"/>
      </button>

      <a v-else :class="{ active: viewingQueue }" class="queue control" href="#!/queue">
        <icon :icon="faListOl"/>
      </a>

      <RepeatModeSwitch/>
      <Volume/>
    </div>
  </div>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { faBolt, faListOl, faSliders } from '@fortawesome/free-solid-svg-icons'
import { ref, toRef, toRefs } from 'vue'
import { eventBus, isAudioContextSupported as useEqualizer } from '@/utils'
import { preferenceStore } from '@/stores'

import Equalizer from '@/components/ui/Equalizer.vue'
import Volume from '@/components/ui/Volume.vue'
import LikeButton from '@/components/song/SongLikeButton.vue'
import RepeatModeSwitch from '@/components/ui/RepeatModeSwitch.vue'

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const showExtraPanel = toRef(preferenceStore.state, 'showExtraPanel')
const showEqualizer = ref(false)
const viewingQueue = ref(false)

const toggleExtraPanel = () => (preferenceStore.showExtraPanel = !showExtraPanel.value)
const toggleEqualizer = () => (showEqualizer.value = !showEqualizer.value)
const closeEqualizer = () => (showEqualizer.value = false)
const toggleVisualizer = () => isMobile.any || eventBus.emit('TOGGLE_VISUALIZER')

eventBus.on('ACTIVATE_SCREEN', (screen: ScreenName) => (viewingQueue.value = screen === 'Queue'))
</script>

<style lang="scss" scoped>
.other-controls {
  @include vertical-center();

  position: relative;
  flex: 0 0 var(--extra-panel-width);
  color: var(--color-text-secondary);

  .wrapper {
    @include vertical-center();

    > * + * {
      margin-left: 1rem;
    }
  }

  .control {
    &.active {
      color: var(--color-accent);
    }

    &:last-child {
      padding-right: 0;
    }
  }

  @media only screen and (max-width: 768px) {
    position: absolute !important;
    right: 0;
    top: 0;
    height: 100%;
    width: 188px;
    padding-top: 12px; // leave space for the audio track

    &::before {
      display: none;
    }

    .queue {
      display: none;
    }

    > * + * {
      margin-left: 1.5rem;
    }
  }
}
</style>
