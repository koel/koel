import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { commonStore } from '@/stores/commonStore'
import { acceptsFile } from '@/utils/mediaHelper'
import type { UploadFile } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'
import { getAllFileEntries } from '@/utils/directoryReader'
import { useRouter } from '@/composables/useRouter'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { usePolicies } from '@/composables/usePolicies'

export const useUpload = () => {
  const { t } = useI18n()
  const { toastSuccess, toastWarning } = useMessageToaster()
  const { go, isCurrentScreen } = useRouter()

  const { currentUserCan } = usePolicies()

  const mediaPathSetUp = computed(() => {
    return commonStore.state.storage_driver !== 'local' || commonStore.state.media_path_set
  })

  const allowsUpload = computed(() => currentUserCan.uploadSongs())

  const fileEntryToFile = async (entry: FileSystemFileEntry) => new Promise<File>(resolve => entry.file(resolve))

  const queueFilesForUpload = (files: Array<File>) => {
    const uploadCandidates = files
      .filter(file => acceptsFile(file))
      .map((file): UploadFile => ({
        file,
        id: `${file.name}-${file.size}`, // for simplicity, a file's identity is determined by its name and size
        status: 'Ready',
        name: file.name,
        progress: 0,
      }))

    uploadService.queue(uploadCandidates)

    return uploadCandidates
  }

  const handleDropEvent = async (event: DragEvent) => {
    if (!event.dataTransfer) {
      return
    }

    const fileEntries = await getAllFileEntries(event.dataTransfer.items)
    const files = await Promise.all(fileEntries.map(entry => fileEntryToFile(entry)))
    const queuedFiles = queueFilesForUpload(files)

    if (queuedFiles.length) {
      const itemLabel = queuedFiles.length === 1 ? t('messages.filesQueued') : t('messages.filesQueuedPlural')
      toastSuccess(t('messages.queuedForUpload', { count: queuedFiles.length, item: itemLabel }))
      isCurrentScreen('Upload') || go('upload')
    } else {
      toastWarning(t('messages.noFilesForUpload'))
    }
  }

  return {
    mediaPathSetUp,
    allowsUpload,
    handleDropEvent,
    queueFilesForUpload,
  }
}
