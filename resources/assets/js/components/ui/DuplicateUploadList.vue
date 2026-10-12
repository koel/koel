<template>
  <section class="flex flex-col gap-4">
    <VirtualScroller :item-height="ROW_HEIGHT" :items="songs" class="flex-1 -mr-6 pr-6">
      <template #default="{ item }: { item: DuplicateUpload }">
        <div :key="item.id" :style="{ height: `${ROW_HEIGHT}px`, paddingBottom: `${ROW_GAP}px` }">
          <DuplicateUploadItem :upload="item" class="h-full" />
        </div>
      </template>
    </VirtualScroller>

    <footer class="flex justify-end gap-2">
      <Btn variant="success" @click="keepAll">Keep All</Btn>
      <Btn variant="destructive" @click="confirmDiscardAll">Discard All</Btn>
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

const ROW_GAP = 6
const ROW_HEIGHT = 36 + ROW_GAP

const { showConfirmDialog } = useDialogBox()

const keepAll = () => uploadService.keepAllDuplicates()

const confirmDiscardAll = async () => {
  if (await showConfirmDialog('Discard all duplicate uploads?')) {
    uploadService.discardAllDuplicates()
  }
}
</script>
