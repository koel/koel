import isMobile from 'ismobilejs'
import { computed } from 'vue'
import { commonStore } from '@/stores'
import { acceptedMediaTypes } from '@/config'
import { UploadFile, uploadService } from '@/services'
import { getAllFileEntries, pluralize } from '@/utils'
import { useMessageToaster, useRouter, usePolicies } from '@/composables'

export const useUpload = () => {
  const { toastSuccess, toastWarning } = useMessageToaster()
  const { go, isCurrentScreen } = useRouter()

  const { currentUserCan } = usePolicies()

  const mediaPathSetUp = computed(() => {
    return commonStore.state.storage_driver !== 'local' || commonStore.state.media_path_set
  })

  const allowsUpload = computed(() => currentUserCan.uploadSongs() && !isMobile.phone)

  const fileEntryToFile = async (entry: FileSystemEntry) => new Promise<File>(resolve => entry.file(resolve))

  const queueFilesForUpload = (files: Array<File>) => {
    const uploadCandidates = files
      .filter(({ type }) => acceptedMediaTypes.includes(type))
      .map((file): UploadFile => ({
        file,
        id: `${file.name}-${file.size}`, // for simplicity, a file's identity is determined by its name and size
        status: 'Ready',
        name: file.name,
        progress: 0
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
      toastSuccess(`Queued ${pluralize(queuedFiles, 'file')} for upload`)
      isCurrentScreen('Upload') || go('upload')
    } else {
      toastWarning('No files applicable for upload')
    }
  }

  return {
    mediaPathSetUp,
    allowsUpload,
    handleDropEvent,
    queueFilesForUpload
  }
}
