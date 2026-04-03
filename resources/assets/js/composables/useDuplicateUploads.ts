import { DuplicateUpload, uploadService } from '@/services/uploadService'
import { computed, toRef } from 'vue'

export const useDuplicateUploads = () => {
  const duplicateFilesUploaded = toRef(uploadService.state, 'duplicateFilesUploaded')
  const duplicatedSongs = computed(() => uploadService.state.duplicatedSongs)

  const fetchDuplicates = async () => {
    return await uploadService.fetchDuplicates()
  }

  const keepDuplicates = async (songs: DuplicateUpload[]) => {
    console.log('keep', songs)
    return await uploadService.keepDuplicates(songs)
  }

  const deleteDuplicates = async (songs: DuplicateUpload[]) => {
    console.log('delete', songs)
    return await uploadService.deleteDuplicates(songs)
  }

  return {
    duplicateFilesUploaded,
    duplicatedSongs,
    fetchDuplicates,
    keepDuplicates,
    deleteDuplicates,
  }
}
