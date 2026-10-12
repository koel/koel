<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="scale-0 opacity-0"
    leave-active-class="transition duration-300 ease-in"
    leave-to-class="scale-0 opacity-0"
    @after-enter="showTooltipBriefly"
  >
    <span v-if="state.status !== 'idle'" class="block">
      <SideSheetButton
        ref="button"
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
import { computed, onBeforeUnmount, useTemplateRef } from 'vue'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { eventBus } from '@/utils/eventBus'
import { zipDownloadService } from '@/services/zipDownloadService'

import ProgressRing from '@/components/ui/ProgressRing.vue'
import SideSheetButton from '@/components/layout/main-wrapper/side-sheet/SideSheetButton.vue'

const TOOLTIP_ON_START_MS = 3_000

const { state } = zipDownloadService
const button = useTemplateRef<InstanceType<typeof SideSheetButton>>('button')
let hideTooltipTimer: number | null = null

const clearHideTooltipTimer = () => {
  if (hideTooltipTimer) {
    window.clearTimeout(hideTooltipTimer)
    hideTooltipTimer = null
  }
}

const showTooltipBriefly = () => {
  const element = button.value?.$el as HTMLElement | undefined

  if (!element) {
    return
  }

  element.dispatchEvent(new Event('mouseenter'))
  clearHideTooltipTimer()

  hideTooltipTimer = window.setTimeout(() => {
    hideTooltipTimer = null

    if (!element.matches(':hover')) {
      element.dispatchEvent(new Event('mouseleave'))
    }
  }, TOOLTIP_ON_START_MS)
}

const { toastSuccess } = useMessageToaster()
const announceSavedArchive = () => toastSuccess('Download complete.')

eventBus.on('DOWNLOAD_ARCHIVE_SAVED', announceSavedArchive)

onBeforeUnmount(() => {
  clearHideTooltipTimer()
  eventBus.off('DOWNLOAD_ARCHIVE_SAVED', announceSavedArchive)
})
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

  if (await showConfirmDialog('Cancel the download?')) {
    zipDownloadService.cancel()
  }
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

:deep([role='progressbar'] circle) {
  transition-duration: 800ms;
  transition-timing-function: linear;

  &:first-child {
    @apply stroke-k-fg-10;
  }
}
</style>
