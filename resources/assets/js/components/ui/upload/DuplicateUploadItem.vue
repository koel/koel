<template>
  <article class="flex items-stretch min-h-[32px] bg-k-fg-5 rounded-lg overflow-hidden">
    <span
      class="self-center min-w-0 flex-1 overflow-hidden whitespace-nowrap px-4 [mask-image:linear-gradient(to_right,black_calc(100%-3rem),transparent)]"
    >
      {{ upload.song_title ? `${upload.artist_name} — ${upload.song_title}` : upload.filename }}
    </span>
    <time
      :datetime="upload.created_at"
      :title="uploadedAt.toLocaleString()"
      class="self-center shrink-0 px-4 text-k-fg-50"
    >
      Uploaded {{ uploadedAgo }}
    </time>
    <Btn class="h-full px-4!" icon-only title="Keep" unrounded variant="success" @click="keep">
      <Icon :icon="faCheck" />
    </Btn>
    <Btn class="h-full px-4!" icon-only title="Discard" unrounded variant="destructive" @click="confirmDiscard">
      <Icon :icon="faTrashCan" />
    </Btn>
  </article>
</template>

<script setup lang="ts">
import { faCheck, faTrashCan } from '@fortawesome/free-solid-svg-icons'
import { useTimeAgo } from '@vueuse/core'
import { useDialogBox } from '@/composables/useDialogBox'
import { uploadService } from '@/services/uploadService'

import Btn from '@/components/ui/form/Btn.vue'

import type { DuplicateUpload } from '@/services/uploadService'

const props = defineProps<{ upload: DuplicateUpload }>()

const uploadedAt = new Date(props.upload.created_at)
const uploadedAgo = useTimeAgo(uploadedAt)

const { showConfirmDialog } = useDialogBox()

const keep = () => uploadService.keepDuplicate(props.upload.id)

const confirmDiscard = async () => {
  if (await showConfirmDialog('Discard this duplicate upload?')) {
    uploadService.discardDuplicate(props.upload.id)
  }
}
</script>
