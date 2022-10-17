<template>
  <div
    id="vizContainer"
    ref="el"
    :class="{ fullscreen: isFullscreen }"
    data-testid="visualizer"
    @dblclick="toggleFullscreen"
  >
    <CloseModalBtn class="close" @click="hide"/>
  </div>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import initVisualizer from '@/utils/visualizer'
import { eventBus, logger } from '@/utils'

import CloseModalBtn from '@/components/ui/BtnCloseModal.vue'

const el = ref<HTMLElement>()
const isFullscreen = ref(false)

const toggleFullscreen = () => {
  isFullscreen.value ? document.exitFullscreen() : el.value?.requestFullscreen()
  isFullscreen.value = !isFullscreen.value
}

const hide = () => eventBus.emit('TOGGLE_VISUALIZER')

onMounted(() => {
  try {
    initVisualizer(el.value!)
  } catch (e) {
    logger.warn('Failed to initialize visualizer', e)
    // in e.g., DOM testing, the call will fail due to the lack of proper API support
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
