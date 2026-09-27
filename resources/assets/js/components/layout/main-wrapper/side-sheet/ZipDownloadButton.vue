<template>
  <SideSheetButton
    v-if="state.status !== 'idle'"
    v-koel-tooltip.left="description"
    :aria-label="description"
    :data-status="state.status"
    class="opacity-100 text-k-fg"
    data-testid="zip-download-button"
    @click.prevent="act"
  >
    <ProgressRing
      :class="state.status === 'failed' ? 'text-k-danger' : 'text-k-highlight'"
      :value="progress"
      class="absolute inset-0 size-full"
    />
    <FileArchiveIcon :size="18" />
  </SideSheetButton>
</template>

<script lang="ts" setup>
import { FileArchiveIcon } from 'lucide-vue-next'
import { computed } from 'vue'
import { useDialogBox } from '@/composables/useDialogBox'
import { zipDownloadService } from '@/services/zipDownloadService'
import { formatBytes } from '@/utils/formatters'

import ProgressRing from '@/components/ui/ProgressRing.vue'
import SideSheetButton from '@/components/layout/main-wrapper/side-sheet/SideSheetButton.vue'

const { state } = zipDownloadService
const { showConfirmDialog } = useDialogBox()

const progress = computed(() => {
  if (state.status !== 'zipping') {
    return 100
  }

  return state.bytesTotal ? Math.min(100, (state.bytesDone / state.bytesTotal) * 100) : 0
})

const description = computed(() => {
  switch (state.status) {
    case 'zipping':
      return `Preparing ${state.archiveName}: ${state.songsDone} of ${state.songsTotal} songs, ${formatBytes(state.bytesDone)} of ${formatBytes(state.bytesTotal)}. Click to cancel.`
    case 'ready':
      return `${state.archiveName} is ready. Click to save.`
    default:
      return `${state.error} Click to dismiss.`
  }
})

const act = async () => {
  if (state.status === 'ready') {
    zipDownloadService.save()
    return
  }

  if (state.status === 'failed') {
    zipDownloadService.dismiss()
    return
  }

  if (await showConfirmDialog(`Stop preparing ${state.archiveName}?`)) {
    zipDownloadService.cancel()
  }
}
</script>
