import { DuplicateUpload, uploadService } from '@/services/uploadService'
import { computed } from 'vue'

export const useDuplicateUploads = () => {
  const duplicateFilesUploaded = uploadService.duplicateFilesUploaded
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
