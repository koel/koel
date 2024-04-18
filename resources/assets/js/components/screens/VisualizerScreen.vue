<template>
  <section id="vizContainer" ref="container" :class="{ fullscreen: isFullscreen }" @dblclick.prevent="toggleFullscreen">
    <div
      class="absolute z-[1] w-full h-full top-0 left-0 opacity-0 transition-opacity
      duration-300 ease-in-out hover:opacity-100"
    >
      <div
        v-if="selectedVisualizer"
        class="absolute bottom-8 left-8 px-6 py-4 bg-black/30 rounded-md"
      >
        <h3 class="text-lg mb-2">{{ selectedVisualizer.name }}</h3>
        <p v-if="selectedVisualizer.credits" class="text-k-text-secondary">
          by {{ selectedVisualizer.credits.author }}
          <a :href="selectedVisualizer.credits.url" class="ml-2" target="_blank">
            <Icon :icon="faUpRightFromSquare" />
          </a>
        </p>
      </div>

      <div class="absolute bottom-8 right-8 border border-white/30 rounded-md">
        <SelectBox v-model="selectedId" class="!bg-black/20 !text-white block">
          <option disabled value="-1">Pick a visualizer</option>
          <option v-for="v in visualizers" :key="v.id" :value="v.id">{{ v.name }}</option>
        </SelectBox>
      </div>
    </div>

    <div ref="el" class="viz h-full w-full absolute z-0" />
  </section>
</template>

<script lang="ts" setup>
import { faUpRightFromSquare } from '@fortawesome/free-solid-svg-icons'
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useFullscreen } from '@vueuse/core'
import { logger } from '@/utils'
import { preferenceStore as preferences, visualizerStore } from '@/stores'

import SelectBox from '@/components/ui/form/SelectBox.vue'

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

<style lang="postcss" scoped>
:deep(canvas) {
  @apply transition-opacity duration-300 h-full w-full;
}

.fullscreen {
  /* :fullscreen pseudo support is kind of buggy, so we use a class instead */
  @apply bg-k-bg-primary;
}
</style>
