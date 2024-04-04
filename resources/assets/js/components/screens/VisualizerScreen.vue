<template>
  <section id="vizContainer" ref="container" :class="{ fullscreen: isFullscreen }" @dblclick.prevent="toggleFullscreen">
    <div class="artifacts">
      <div v-if="selectedVisualizer" class="credits">
        <h3>{{ selectedVisualizer.name }}</h3>
        <p v-if="selectedVisualizer.credits" class="text-secondary">
          by {{ selectedVisualizer.credits.author }}
          <a :href="selectedVisualizer.credits.url" target="_blank">
            <Icon :icon="faUpRightFromSquare" />
          </a>
        </p>
      </div>

      <select v-model="selectedId">
        <option disabled value="-1">Pick a visualizer</option>
        <option v-for="v in visualizers" :key="v.id" :value="v.id">{{ v.name }}</option>
      </select>
    </div>
    <div ref="el" class="viz" />
  </section>
</template>

<script lang="ts" setup>
import { faUpRightFromSquare } from '@fortawesome/free-solid-svg-icons'
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useFullscreen } from '@vueuse/core'
import { logger } from '@/utils'
import { preferenceStore as preferences, visualizerStore } from '@/stores'

const visualizers = visualizerStore.all
let destroyVisualizer: () => void

const el = ref<HTMLElement | null>(null)
const container = ref<HTMLElement | null>(null)
const selectedId = ref<Visualizer['id']>()

const { isFullscreen, toggle: toggleFullscreen } = useFullscreen(container)

const render = async (viz: Visualizer) => {
  if (!el.value) {
    await nextTick()
    await render(viz)
  }

  freeUp()

  try {
    destroyVisualizer = await viz.init(el.value!)
  } catch (e) {
    // in e.g., DOM testing, the call will fail due to the lack of proper API support
    logger.warn('Failed to initialize visualizer', e)
  }
}

const selectedVisualizer = ref<Visualizer>()

watch(selectedId, id => {
  preferences.visualizer = id
  selectedVisualizer.value = visualizerStore.getVisualizerById(id || 'default')!
  render(selectedVisualizer.value)
})

onMounted(() => {
  selectedId.value = preferences.visualizer || 'default'

  if (!visualizerStore.getVisualizerById(selectedId.value)) {
    selectedId.value = 'default'
  }
})

const freeUp = () => {
  destroyVisualizer?.()
  el.value && (el.value.innerHTML = '')
}

onBeforeUnmount(() => freeUp())
</script>

<style lang="postcss">
#vizContainer {
  .viz {
    height: 100%;
    width: 100%;
    position: absolute;
    z-index: 0;

    canvas {
      transition: opacity 0.3s;
      height: 100%;
      width: 100%;
    }
  }

  .artifacts {
    position: absolute;
    z-index: 1;
    height: 100%;
    width: 100%;
    top: 0;
    left: 0;
    opacity: 0;
    padding: 24px;
    transition: opacity 0.3s ease-in-out;
  }

  &:hover {
    .artifacts {
      opacity: 1;
    }
  }

  .credits {
    padding: 14px 28px 14px 14px;
    background: rgba(0, 0, 0, .5);
    width: fit-content;
    position: absolute;
    bottom: 24px;

    h3 {
      font-size: 1.2rem;
      margin-bottom: .3rem;
    }

    a {
      margin-left: .5rem;
      display: inline-block;
      vertical-align: middle;
    }
  }

  select {
    position: absolute;
    bottom: 24px;
    right: 24px;
  }

  &.fullscreen {
    // :fullscreen pseudo support is kind of buggy, so we use a class instead.
    background: var(--color-bg-primary);

    .close {
      opacity: 0 !important;
    }
  }
}
</style>
