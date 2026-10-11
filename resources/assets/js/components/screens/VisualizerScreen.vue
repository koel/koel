<template>
  <section
    id="vizContainer"
    ref="container"
    :class="{ fullscreen: isFullscreen, 'cursor-none': isFullscreen && controlsHidden }"
    @dblclick.prevent="toggleFullscreen"
    @mousemove="showControls"
  >
    <div
      class="controls absolute z-1 w-full h-full top-0 left-0 opacity-0 transition-opacity duration-300 ease-in-out"
      :class="{ 'hover:opacity-100': !isFullscreen, 'show-controls': !controlsHidden && isFullscreen }"
    >
      <div v-if="selectedVisualizer" class="absolute bottom-8 left-8 px-6 py-4 bg-black/30 rounded-md">
        <h3 class="text-lg mb-2">{{ selectedVisualizer.name }}</h3>
        <p v-if="selectedVisualizer.credits">
          by {{ selectedVisualizer.credits.author }}
          <a :href="selectedVisualizer.credits.url" class="ml-2" target="_blank">
            <Icon :icon="faUpRightFromSquare" />
          </a>
        </p>
      </div>

      <div class="absolute bottom-8 right-8 flex gap-2">
        <SelectBox v-model="selectedId" class="bg-black/20! text-white! block border border-white/30 rounded-md">
          <option disabled value="-1">Pick a visualizer</option>
          <option v-for="v in visualizers" :key="v.id" :value="v.id">{{ v.name }}</option>
        </SelectBox>
        <template v-if="selectedVisualizer?.importsMilkdropPresets">
          <SelectBox
            v-model="presetChoice"
            aria-label="Preset"
            class="bg-black/20! text-white! block border border-white/30 rounded-md max-w-64"
          >
            <option :value="RANDOM_PRESET">Random</option>
            <option v-for="name in presetState.names" :key="name" :value="name">{{ name }}</option>
            <option :value="IMPORT_PRESETS">Import presets…</option>
          </SelectBox>
          <input
            ref="presetFileInput"
            accept=".milk"
            aria-label="Preset files"
            class="hidden"
            multiple
            type="file"
            @change="importMilkdropPresets"
          />
        </template>
      </div>
    </div>

    <div ref="el" class="viz h-full w-full absolute z-0" />
  </section>
</template>

<script lang="ts" setup>
import { faUpRightFromSquare } from '@fortawesome/free-solid-svg-icons'
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useFullscreen } from '@vueuse/core'
import { useThrottleFn } from '@vueuse/core'
import { logger } from '@/utils/logger'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { visualizerStore } from '@/stores/visualizerStore'
import { milkdropPresetStore, RANDOM_PRESET } from '@/stores/milkdropPresetStore'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { pluralize } from '@/utils/formatters'

import SelectBox from '@/components/ui/form/SelectBox.vue'

const visualizers = visualizerStore.all
let destroyVisualizer: () => void
let hideControlsTimeout: number

const el = ref<HTMLElement | null>(null)
const container = ref<HTMLElement | null>(null)
const selectedId = ref<Visualizer['id']>()

const { isFullscreen, toggle: toggleFullscreen } = useFullscreen(container)

const controlsHidden = ref(false)

const setupControlHidingTimer = () => {
  window.clearTimeout(hideControlsTimeout)
  hideControlsTimeout = window.setTimeout(() => (controlsHidden.value = true), 5000)
}

const showControls = useThrottleFn(() => {
  if (!isFullscreen.value) {
    return
  }

  controlsHidden.value = false
  setupControlHidingTimer()
}, 100)

watch(isFullscreen, fullscreen => {
  if (fullscreen) {
    setupControlHidingTimer()
  } else {
    window.clearTimeout(hideControlsTimeout)
    controlsHidden.value = false
  }
})

const freeUp = () => {
  destroyVisualizer?.()
  el.value && (el.value.innerHTML = '')
}

const render = async (viz: Visualizer) => {
  if (!el.value) {
    await nextTick()
    await render(viz)
  }

  freeUp()

  try {
    destroyVisualizer = await viz.init(el.value!)
  } catch (error: unknown) {
    // in e.g., DOM testing, the call will fail due to the lack of proper API support
    logger.warn('Failed to initialize visualizer', error)
  }
}

const selectedVisualizer = ref<Visualizer>()

const IMPORT_PRESETS = 'import-presets'

const { state: presetState } = milkdropPresetStore
const { toastSuccess, toastWarning } = useMessageToaster()
const presetFileInput = ref<HTMLInputElement>()
const presetChoice = ref(presetState.selected)

watch(
  () => presetState.selected,
  selected => (presetChoice.value = selected),
)

watch(presetChoice, async choice => {
  if (choice === IMPORT_PRESETS) {
    presetFileInput.value?.click()
    await nextTick()
    presetChoice.value = presetState.selected
    return
  }

  milkdropPresetStore.select(choice)
})

const importMilkdropPresets = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const { importedNames, skippedCount } = await milkdropPresetStore.importFiles(input.files ?? [])

  input.value = ''

  if (importedNames.length) {
    toastSuccess(`Imported ${pluralize(importedNames, 'preset')}.`)
  }

  if (skippedCount) {
    toastWarning(`Skipped ${pluralize(skippedCount, 'file')} that aren't .milk presets.`)
  }
}

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

onBeforeUnmount(() => {
  window.clearTimeout(hideControlsTimeout)
  freeUp()
})
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
:deep(canvas) {
  @apply transition-opacity duration-300 h-full w-full;
}

.fullscreen {
  /* :fullscreen pseudo support is kind of buggy, so we use a class instead */
  @apply bg-k-bg;
}

.controls.show-controls {
  @apply opacity-100;
}

.fullscreen .controls:not(.show-controls) {
  @apply pointer-events-none;
}
</style>
