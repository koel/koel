import { computed, watch } from 'vue'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { zipDownloadService } from '@/services/zipDownloadService'
import { formatBytes } from '@/utils/formatters'

import ProgressRing from '@/components/ui/ProgressRing.vue'

export const useZipDownloadToast = () => {
  const { toastPromise, dismissToast } = useMessageToaster()
  const { showConfirmDialog } = useDialogBox()
  const { state } = zipDownloadService

  let currentToastId: string | null = null

  const progress = computed(() => {
    const [done, total] = state.allSizesKnown
      ? [state.bytesDone, state.bytesTotal]
      : [state.itemsDone, state.itemsTotal]

    return total ? Math.min(100, (done / total) * 100) : 0
  })

  const progressDetails = computed(() => {
    const items = `${state.itemsDone.toLocaleString()} of ${state.itemsTotal.toLocaleString()} items`

    return state.allSizesKnown
      ? `${items} · ${formatBytes(state.bytesDone)} of ${formatBytes(state.bytesTotal)}`
      : items
  })

  const confirmCancel = async () => {
    if (await showConfirmDialog('Cancel the download?')) {
      zipDownloadService.cancel()
    }
  }

  const waitForArchive = async () => {
    await zipDownloadService.building

    if (state.status === 'failed') {
      throw new Error(state.error)
    }

    return state.status
  }

  const saveArchive = (toastId: string) => {
    zipDownloadService.save()
    dismissToast(toastId)
  }

  const dismissUnsavedArchive = () => state.status === 'ready' && zipDownloadService.dismiss()

  const toastZipDownload = () => {
    currentToastId && dismissToast(currentToastId)

    const toastId = toastPromise(waitForArchive(), {
      loading: {
        content: 'Preparing download…',
        icon: ProgressRing,
        iconProps: { value: progress, title: progressDetails, class: 'size-4' },
        actions: [
          {
            label: 'Cancel',
            handler: confirmCancel,
          },
        ],
      },
      success: {
        content: 'Download ready.',
        actions: [
          {
            label: 'Save',
            handler: () => saveArchive(toastId),
            primary: true,
          },
        ],
        onDismiss: dismissUnsavedArchive,
      },
      warning: status => (status === 'ready' ? undefined : 'Download canceled'),
      error: () => state.error,
    })

    currentToastId = toastId
  }

  watch(
    () => state.status,
    status => status === 'zipping' && toastZipDownload(),
  )
}
