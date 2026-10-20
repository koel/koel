import { computed } from 'vue'
import { Filter } from '@/config/hooks'
import { applyFilters } from '@/hooks'
import { commonStore } from '@/stores/commonStore'
import { acceptsFile } from '@/utils/mediaHelper'
import type { UploadFile } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'
import { getAllFileEntries } from '@/utils/directoryReader'
import { pluralize } from '@/utils/formatters'
import { useRouter } from '@/composables/useRouter'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { canUploadFromThisDevice } from '@/utils/uploadAccess'
import { uuid } from '@/utils/crypto'

export const useUpload = () => {
  const { toastSuccess, toastWarning } = useMessageToaster()
  const { go, isCurrentScreen } = useRouter()

  const mediaPathSetUp = computed(() => {
    return commonStore.state.storage_driver !== 'local' || commonStore.state.media_path_set
  })

  const allowsUpload = computed(canUploadFromThisDevice)

  const unfinishedUploadCount = computed(() => uploadService.countUnfinishedFiles())

  const fileEntryToFile = async (entry: FileSystemFileEntry) => new Promise<File>(resolve => entry.file(resolve))

  const queueFilesForUpload = (files: Array<File>) => {
    const entries = applyFilters<UploadFile[]>(
      Filter.SELECTED_UPLOADS,
      files.map((file): UploadFile => ({
        file,
        id: uuid(),
        status: acceptsFile(file) ? 'Ready' : 'Skipped',
        message: acceptsFile(file) ? undefined : 'Unsupported format',
        name: file.name,
        progress: 0,
      })),
    )

    const acceptedEntries = entries.filter(({ status }) => status === 'Ready')
    uploadService.queue(entries)

    return acceptedEntries
  }

  const handleDropEvent = async (event: DragEvent) => {
    if (!event.dataTransfer) {
      return
    }

    const fileEntries = await getAllFileEntries(event.dataTransfer.items)
    const files = await Promise.all(fileEntries.map(entry => fileEntryToFile(entry)))
    const queuedFiles = queueFilesForUpload(files)

    if (queuedFiles.length) {
      toastSuccess(`Queued ${pluralize(queuedFiles, 'file')} for upload`)
      isCurrentScreen('Upload') || go('upload')
    } else {
      toastWarning('No files applicable for upload')
    }
  }

  return {
    mediaPathSetUp,
    allowsUpload,
    unfinishedUploadCount,
    handleDropEvent,
    queueFilesForUpload,
  }
}
