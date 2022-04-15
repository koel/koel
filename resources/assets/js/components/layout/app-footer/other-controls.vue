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

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { socket } from '@/services'
import { eventBus, isAudioContextSupported } from '@/utils'
import { favoriteStore, preferenceStore, sharedStore, songStore } from '@/stores'
import isMobile from 'ismobilejs'

export default Vue.extend({
  props: {
    song: {
      type: Object
    } as PropOptions<Song>
  },

  components: {
    Equalizer: () => import('@/components/ui/equalizer.vue'),
    SoundBar: () => import('@/components/ui/sound-bar.vue'),
    Volume: () => import('@/components/ui/volume.vue'),
    LikeButton: () => import('@/components/song/like-button.vue'),
    RepeatModeSwitch: () => import('@/components/ui/repeat-mode-switch.vue')
  },

  data: () => ({
    preferences: preferenceStore.state,
    showEqualizer: false,
    sharedState: sharedStore.state,
    useEqualizer: isAudioContextSupported,
    viewingQueue: false
  }),

  methods: {
    like (): void {
      if (this.song.id) {
        favoriteStore.toggleOne(this.song)
        socket.broadcast('SOCKET_SONG', songStore.generateDataToBroadcast(this.song))
      }
    },

    toggleExtraPanel (): void {
      preferenceStore.showExtraPanel = !this.preferences.showExtraPanel
    },

    toggleEqualizer (): void {
      this.showEqualizer = !this.showEqualizer
    },

    closeEqualizer (): void {
      this.showEqualizer = false
    },

    toggleVisualizer: (): void => {
      if (!isMobile.any) {
        eventBus.emit('TOGGLE_VISUALIZER')
      }
    }
  },

  created (): void {
    eventBus.on('LOAD_MAIN_CONTENT', (view: MainViewName): void => {
      this.viewingQueue = view === 'Queue'
    })
  }
})
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
