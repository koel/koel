<template>
  <div
    :class="{ fullscreen: isFullscreen }"
    @dblclick="toggleFullscreen"
    id="vizContainer"
    ref="visualizerContainer"
    data-testid="visualizer"
  >
    <close-modal-btn class="close" @click="hide"/>
  </div>
</template>

<script lang="ts">
import Vue from 'vue'
import initVisualizer from '@/utils/visualizer'
import { eventBus } from '@/utils'

export default Vue.extend({
  components: {
    CloseModalBtn: () => import('@/components/ui/close-modal-btn.vue')
  },

  data: () => ({
    isFullscreen: false
  }),

  methods: {
    toggleFullscreen (): void {
      if (this.isFullscreen) {
        document.exitFullscreen()
      } else {
        (this.$refs.visualizerContainer as HTMLElement).requestFullscreen()
      }

      this.isFullscreen = !this.isFullscreen
    },

    hide: (): void => {
      eventBus.emit('TOGGLE_VISUALIZER')
    }
  },

  mounted (): void {
    initVisualizer(this.$refs.visualizerContainer as HTMLElement)
  }
})
</script>

<style lang="scss" scoped>
#vizContainer {
  position: relative;

  &.fullscreen {
    // :fullscreen pseudo support is kind of buggy, so we use a class instead.
    background: var(--color-bg-primary);

    .close {
      opacity: 0 !important;
    }
  }

  .close {
    opacity: 0;
  }

  &:hover {
    .close {
      opacity: 1;
    }
  }
}
</style>
