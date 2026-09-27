<template>
  <article class="flex items-center min-h-[32px] bg-k-fg-5 rounded-lg overflow-hidden">
    <span
      class="min-w-0 flex-1 overflow-hidden whitespace-nowrap px-4 [mask-image:linear-gradient(to_right,black_calc(100%-3rem),transparent)]"
    >
      {{ upload.song_title ? `${upload.artist_name} — ${upload.song_title}` : upload.filename }}
    </span>
    <span class="shrink-0 px-4 text-k-fg-70">Uploaded {{ new Date(upload.created_at).toLocaleDateString() }}</span>
    <div class="flex shrink-0 gap-2 pr-1">
      <Btn size="small" variant="destructive" @click="confirmDiscard">Discard</Btn>
      <Btn size="small" variant="success" @click="keep">Keep</Btn>
    </div>
  </article>
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
