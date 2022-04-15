<template>
  <div class="other-controls" data-testid="other-controls">
    <div class="wrapper" v-koel-clickaway="closeEqualizer">
      <equalizer v-show="showEqualizer" v-if="useEqualizer"/>

      <button
        v-if="song && song.playbackState === 'Playing'"
        @click.prevent="toggleVisualizer"
        title="Click for a marvelous visualizer!"
        data-testid="toggle-visualizer-btn"
      >
        <sound-bar data-testid="sound-bar-play"/>
      </button>

      <like-button v-if="song" :song="song" class="like"/>

      <button
        :class="{ active: preferences.showExtraPanel }"
        @click.prevent="toggleExtraPanel"
        class="control text-uppercase"
        title="View song information"
        data-testid="toggle-extra-panel-btn"
      >
        Info
      </button>

      <button
        @click.prevent="toggleEqualizer"
        v-if="useEqualizer"
        class="control equalizer"
        :title="`${ showEqualizer ? 'Hide' : 'Show'} equalizer`"
        :class="{ active: showEqualizer }"
        data-testid="toggle-equalizer-btn"
      >
        <i class="fa fa-sliders"></i>
      </button>

      <a v-else class="queue control" :class="{ active: viewingQueue }" href="#!/queue">
        <i class="fa fa-list-ol"></i>
      </a>

      <repeat-mode-switch/>
      <volume/>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, reactive, ref, toRefs } from 'vue'
import { socket } from '@/services'
import { eventBus, isAudioContextSupported as useEqualizer } from '@/utils'
import { favoriteStore, preferenceStore, sharedStore, songStore } from '@/stores'
import isMobile from 'ismobilejs'

const Equalizer = defineAsyncComponent(() => import('@/components/ui/equalizer.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/sound-bar.vue'))
const Volume = defineAsyncComponent(() => import('@/components/ui/volume.vue'))
const LikeButton = defineAsyncComponent(() => import('@/components/song/like-button.vue'))
const RepeatModeSwitch = defineAsyncComponent(() => import('@/components/ui/repeat-mode-switch.vue'))

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const sharedState = reactive(sharedStore.state)
const preferences = reactive(preferenceStore.state)
const showEqualizer = ref(false)
const viewingQueue = ref(false)

const like = () => {
  if (song.value.id) {
    favoriteStore.toggleOne(song.value)
    socket.broadcast('SOCKET_SONG', songStore.generateDataToBroadcast(song.value))
  }
}

const toggleExtraPanel = () => (preferenceStore.showExtraPanel = !preferences.showExtraPanel)
const toggleEqualizer = () => (showEqualizer.value = !showEqualizer.value)
const closeEqualizer = () => showEqualizer.value = false
const toggleVisualizer = () => isMobile.any || eventBus.emit('TOGGLE_VISUALIZER')

eventBus.on('LOAD_MAIN_CONTENT', (view: MainViewName) => (viewingQueue.value = view === 'Queue'))
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
      color: var(--color-highlight);
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
