<template>
  <div v-koel-focus class="md:w-[560px]" tabindex="0" @keydown.esc="close">
    <header class="items-center gap-4">
      <h1 class="flex-1">MilkDrop Presets</h1>
      <Btn bordered variant="ghost" @click="fileInput?.click()">Import</Btn>
      <input
        ref="fileInput"
        accept=".milk"
        aria-label="Preset files"
        class="hidden"
        multiple
        type="file"
        @change="importPresets"
      />
      <button
        class="w-8 h-8 inline-grid place-items-center rounded-full text-k-fg-70 hover:text-k-fg hover:bg-k-fg-5"
        type="button"
        @click="close"
      >
        <XIcon :size="16" />
        <span class="sr-only">Close</span>
      </button>
    </header>

    <main class="flex flex-col gap-4">
      <p class="text-k-fg-50 text-sm">
        Presets are saved in this browser only. You can download more presets
        <a
          :href="PRESET_DOWNLOAD_URL"
          class="inline-flex items-center gap-1 text-k-highlight hover:text-k-fg"
          target="_blank"
        >
          here
          <ExternalLinkIcon :size="12" /> </a
        >.
      </p>

      <TextInput
        v-if="state.names.length"
        v-model="keywords"
        aria-label="Search presets"
        placeholder="Search"
        type="search"
      />

      <ul v-if="shownNames.length" class="max-h-[50vh] overflow-auto">
        <MilkdropPresetListItem v-for="name in shownNames" :key="name" :name />
      </ul>
      <p v-else class="text-k-fg-50">{{ state.names.length ? 'No presets match.' : 'No presets yet.' }}</p>

      <p v-if="matchingNames.length > shownNames.length" class="text-k-fg-50 text-sm">
        Showing {{ shownNames.length }} of {{ matchingNames.length.toLocaleString() }}. Search to narrow down.
      </p>
    </main>
  </div>
</template>

<script lang="ts" setup>
import { ExternalLinkIcon, XIcon } from 'lucide-vue-next'
import { computed, ref, toRef } from 'vue'
import { milkdropPresetStore } from '@/stores/milkdropPresetStore'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useFuzzySearch } from '@/composables/useFuzzySearch'
import { pluralize } from '@/utils/formatters'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import MilkdropPresetListItem from '@/components/visualizer/MilkdropPresetListItem.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const PRESET_DOWNLOAD_URL = 'https://github.com/projectM-visualizer/presets-cream-of-the-crop'
const SHOWN_LIMIT = 200

const { state } = milkdropPresetStore
const { toastSuccess, toastWarning } = useMessageToaster()

const keywords = ref('')
const fileInput = ref<HTMLInputElement>()

const fuzzy = useFuzzySearch(toRef(state, 'names'), [])

const matchingNames = computed(() => {
  const names = state.names

  return keywords.value.trim() ? fuzzy.search(keywords.value) : names
})

const shownNames = computed(() => matchingNames.value.slice(0, SHOWN_LIMIT))

const importPresets = async (event: Event) => {
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

const close = () => emit('close')
</script>
