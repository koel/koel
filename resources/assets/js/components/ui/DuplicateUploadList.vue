<template>
  <section class="flex flex-col gap-4">
    <VirtualScroller :item-height="48" :items="songs" class="flex-1">
      <template #default="{ item }: { item: DuplicateUpload }">
        <div :key="item.id" class="h-12 pb-4">
          <DuplicateUploadItem :upload="item" class="h-full" />
        </div>
      </template>
    </VirtualScroller>

    <footer class="flex justify-end gap-2">
      <Btn size="small" variant="destructive" @click="confirmDiscardAll">Discard All</Btn>
      <Btn size="small" variant="success" @click="keepAll">Keep All</Btn>
    </footer>
  </section>
</template>

<script setup lang="ts">
import { useDialogBox } from '@/composables/useDialogBox'
import { uploadService } from '@/services/uploadService'

import Btn from '@/components/ui/form/Btn.vue'
import DuplicateUploadItem from '@/components/ui/upload/DuplicateUploadItem.vue'
import VirtualScroller from '@/components/ui/VirtualScroller.vue'

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
