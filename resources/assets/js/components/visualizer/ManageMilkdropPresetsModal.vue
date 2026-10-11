<template>
  <div v-koel-focus class="md:w-[560px]" tabindex="0" @keydown.esc="close">
    <header class="items-center gap-4">
      <h1 class="flex-1">MilkDrop Presets</h1>
      <button class="w-8 h-8 rounded-full text-k-fg-70 hover:text-k-fg hover:bg-k-fg-5" type="button" @click="close">
        <XIcon :size="16" />
        <span class="sr-only">Close</span>
      </button>
    </header>

    <main class="flex flex-col gap-4">
      <p class="text-k-fg-50 text-sm">Presets are saved in this browser only.</p>

      <div class="flex gap-2">
        <TextInput v-model="keywords" aria-label="Search presets" class="flex-1" placeholder="Search" type="search" />
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
      </div>

      <ul v-if="shownNames.length" class="max-h-[50vh] overflow-auto">
        <MilkdropPresetListItem v-for="name in shownNames" :key="name" :name />
      </ul>
      <p v-else class="text-k-fg-50">{{ state.names.length ? 'No presets match.' : 'No presets yet.' }}</p>

      <p v-if="matchingNames.length > shownNames.length" class="text-k-fg-50 text-sm">
        Showing {{ shownNames.length }} of {{ matchingNames.length.toLocaleString() }}. Search to narrow down.
      </p>

      <a
        :href="PRESET_DOWNLOAD_URL"
        class="inline-flex items-center gap-1.5 self-start text-k-highlight hover:text-k-fg"
        target="_blank"
      >
        Download more presets
        <ExternalLinkIcon :size="14" />
      </a>
    </main>
  </div>
</template>

<script lang="ts" setup>
import { ExternalLinkIcon, XIcon } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { milkdropPresetStore } from '@/stores/milkdropPresetStore'
import { useMessageToaster } from '@/composables/useMessageToaster'
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

const matchingNames = computed(() => {
  const search = keywords.value.trim().toLowerCase()

  return search ? state.names.filter(name => name.toLowerCase().includes(search)) : state.names
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
