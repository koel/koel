<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="scale-0 opacity-0"
    leave-active-class="transition duration-300 ease-in"
    leave-to-class="scale-0 opacity-0"
  >
    <span v-if="state.status !== 'idle'" class="block">
      <SideSheetButton
        v-koel-tooltip.left="description"
        :aria-label="description"
        :data-status="state.status"
        class="opacity-100 text-k-fg"
        data-testid="zip-download-button"
        @click.prevent="act"
      >
        <ProgressRing
          :class="state.status === 'failed' ? 'text-k-danger' : 'text-k-highlight'"
          :thickness="1.75"
          :value="progress"
          class="absolute inset-0 size-full"
        />
        <FileArchiveIcon :size="14" />
      </SideSheetButton>
    </span>
  </Transition>
</template>

<script lang="ts" setup>
import { FileArchiveIcon } from 'lucide-vue-next'
import { computed } from 'vue'
import { useDialogBox } from '@/composables/useDialogBox'
import { zipDownloadService } from '@/services/zipDownloadService'

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
      return `Preparing download – ${Math.floor(progress.value)}%`
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

  if (await showConfirmDialog('Cancel downloading process?')) {
    zipDownloadService.cancel()
  }
}
</script>
