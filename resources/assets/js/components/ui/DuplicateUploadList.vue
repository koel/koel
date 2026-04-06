<template>
  <details class="rounded-lg border border-k-fg-10 overflow-hidden">
    <summary
      class="flex items-center justify-between px-4 py-3 hover:bg-k-fg-3 text-k-highlight cursor-pointer list-none"
    >
      <span class="flex items-center gap-3">
        <Icon :icon="faCopy" />
        <strong>Duplicate file{{ songs.length === 1 ? '' : 's' }}</strong>
        <span class="text-xs bg-k-highlight text-k-highlight-fg px-2 py-0.5 rounded-full uppercase font-bold">
          {{ songs.length }}
        </span>
      </span>
    </summary>

    <section class="border-t border-k-fg-5">
      <DuplicateUploadItem v-for="upload in songs" :key="upload.id" :upload />

      <footer class="flex justify-end gap-2 px-4 py-3 bg-k-fg-3">
        <Btn small highlight @click="confirmDiscardAll">Discard All</Btn>
        <Btn small success @click="keepAll">Keep All</Btn>
      </footer>
    </section>
  </details>
</template>

<script setup lang="ts">
import { faCopy } from '@fortawesome/free-solid-svg-icons'
import { useDialogBox } from '@/composables/useDialogBox'
import { uploadService } from '@/services/uploadService'

import Btn from '@/components/ui/form/Btn.vue'
import DuplicateUploadItem from '@/components/ui/upload/DuplicateUploadItem.vue'

import type { DuplicateUpload } from '@/services/uploadService'

defineProps<{ songs: DuplicateUpload[] }>()

const { showConfirmDialog } = useDialogBox()

const keepAll = () => uploadService.keepAllDuplicates()

const confirmDiscardAll = async () => {
  if (await showConfirmDialog('Discard all duplicate uploads?')) {
    uploadService.discardAllDuplicates()
  }
}
</script>
