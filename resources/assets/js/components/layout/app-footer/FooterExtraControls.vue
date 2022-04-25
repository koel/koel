<template>
  <div class="other-controls" data-testid="other-controls">
    <div v-koel-clickaway="closeEqualizer" class="wrapper">
      <Equalizer v-show="showEqualizer" v-if="useEqualizer"/>

      <button
        v-if="song?.playbackState === 'Playing'"
        data-testid="toggle-visualizer-btn"
        title="Click for a marvelous visualizer!"
        type="button"
        @click.prevent="toggleVisualizer"
      >
        <SoundBar data-testid="sound-bar-play"/>
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
        <i class="fa fa-sliders"></i>
      </button>

      <a v-else :class="{ active: viewingQueue }" class="queue control" href="#!/queue">
        <i class="fa fa-list-ol"></i>
      </a>

      <RepeatModeSwitch/>
      <Volume/>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref, toRef, toRefs } from 'vue'
import isMobile from 'ismobilejs'
import { socketService } from '@/services'
import { eventBus, isAudioContextSupported as useEqualizer } from '@/utils'
import { favoriteStore, preferenceStore, songStore } from '@/stores'

const Equalizer = defineAsyncComponent(() => import('@/components/ui/Equalizer.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/SoundBar.vue'))
const Volume = defineAsyncComponent(() => import('@/components/ui/Volume.vue'))
const LikeButton = defineAsyncComponent(() => import('@/components/song/SongLikeButton.vue'))
const RepeatModeSwitch = defineAsyncComponent(() => import('@/components/ui/RepeatModeSwitch.vue'))

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const showExtraPanel = toRef(preferenceStore.state, 'showExtraPanel')
const showEqualizer = ref(false)
const viewingQueue = ref(false)

const like = () => {
  if (song.value.id) {
    favoriteStore.toggleOne(song.value)
    socketService.broadcast('SOCKET_SONG', songStore.generateDataToBroadcast(song.value))
  }
}

const toggleExtraPanel = () => (preferenceStore.showExtraPanel = !showExtraPanel.value)
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
