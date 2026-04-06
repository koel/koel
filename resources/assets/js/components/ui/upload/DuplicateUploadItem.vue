<template>
  <div class="flex items-center gap-2 px-4 py-2.5 border-b border-k-fg-5 last:border-b-0">
    <span class="flex-1 min-w-0">
      <span class="block truncate">
        {{ upload.song_title ? `${upload.artist_name} — ${upload.song_title}` : upload.filename }}
      </span>
      <span class="block text-k-fg-50 text-[0.85rem]">
        Uploaded {{ new Date(upload.created_at).toLocaleDateString() }}
      </span>
    </span>
    <Btn small bordered highlight @click="confirmDiscard">Discard</Btn>
    <Btn small bordered success @click="keep">Keep</Btn>
  </div>
</template>

<script setup lang="ts">
import { useDialogBox } from '@/composables/useDialogBox'
import { uploadService } from '@/services/uploadService'

import Btn from '@/components/ui/form/Btn.vue'

import type { DuplicateUpload } from '@/services/uploadService'

const props = defineProps<{ upload: DuplicateUpload }>()

const { showConfirmDialog } = useDialogBox()

const keep = () => uploadService.keepDuplicate(props.upload.id)

const confirmDiscard = async () => {
  if (await showConfirmDialog('Discard this duplicate upload?')) {
    uploadService.discardDuplicate(props.upload.id)
  }
}
</script>
