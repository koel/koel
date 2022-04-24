<template>
  <div
    :class="{ fullscreen: isFullscreen }"
    @dblclick="toggleFullscreen"
    id="vizContainer"
    ref="el"
    data-testid="visualizer"
  >
    <close-modal-btn class="close" @click="hide"/>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted, ref } from 'vue'
import initVisualizer from '@/utils/visualizer'
import { eventBus } from '@/utils'

const CloseModalBtn = defineAsyncComponent(() => import('@/components/ui/BtnCloseModal.vue'))

const el = ref(null as unknown as HTMLElement)
const isFullscreen = ref(false)

const toggleFullscreen = () => {
  isFullscreen.value ? document.exitFullscreen() : el.value.requestFullscreen()
  isFullscreen.value = !isFullscreen.value
}

const hide = () => eventBus.emit('TOGGLE_VISUALIZER')

onMounted(() => initVisualizer(el.value))
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
