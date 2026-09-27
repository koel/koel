<template>
  <section class="rounded-lg border border-k-fg-10 overflow-hidden">
    <DuplicateUploadItem v-for="upload in songs" :key="upload.id" :upload />

    <footer class="flex justify-end gap-2 px-4 py-3 bg-k-fg-3">
      <Btn size="small" variant="highlight" @click="confirmDiscardAll">Discard All</Btn>
      <Btn size="small" variant="success" @click="keepAll">Keep All</Btn>
    </footer>
  </section>
</template>

<script setup lang="ts">
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
