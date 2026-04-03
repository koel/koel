import { uploadService } from '@/services/uploadService'
import { computed, toRef } from 'vue'

export const useDuplicateUploads = () => {
  const duplicateFilesUploaded = toRef(uploadService.state, 'duplicateFilesUploaded')
  const duplicatedSongs = computed(() => uploadService.state.duplicatedSongs)

  const fetchDuplicates = async () => {
    return await uploadService.fetchDuplicates()
  }

  const keepDuplicates = async (duplicateUploadIds: string[]) => {
    return await uploadService.keepDuplicates(duplicateUploadIds)
  }

  const deleteDuplicates = async (duplicateUploadIds: string[]) => {
    return await uploadService.deleteDuplicates(duplicateUploadIds)
  }

  return {
    duplicateFilesUploaded,
    duplicatedSongs,
    fetchDuplicates,
    keepDuplicates,
    deleteDuplicates,
  }
}
