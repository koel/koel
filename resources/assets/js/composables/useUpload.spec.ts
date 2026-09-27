import { describe, expect, it, vi } from 'vite-plus/test'
import { commonStore } from '@/stores/commonStore'
import { uploadService } from '@/services/uploadService'
import type { UploadFile, UploadStatus } from '@/services/uploadService'

vi.mock('@/utils/mediaHelper', () => ({
  acceptedExtensions: ['mp3', 'flac', 'ogg'],
  acceptsFile: (file: File) => file.name.endsWith('.mp3'),
  getFileExtension: (name: string) => name.split('.').pop(),
}))

vi.mock('@/composables/useMessageToaster', () => ({
  useMessageToaster: () => ({
    toastSuccess: vi.fn(),
    toastWarning: vi.fn(),
  }),
}))

vi.mock('@/composables/useRouter', () => ({
  useRouter: () => ({
    go: vi.fn(),
    isCurrentScreen: vi.fn().mockReturnValue(false),
  }),
}))

vi.mock('@/composables/usePolicies', () => ({
  usePolicies: () => ({
    currentUserCan: {
      uploadSongs: () => true,
    },
  }),
}))

import { useUpload } from './useUpload'

describe('useUpload', () => {
  it('computes mediaPathSetUp when storage is not local', () => {
    commonStore.state.storage_driver = 's3'

    const { mediaPathSetUp } = useUpload()
    expect(mediaPathSetUp.value).toBe(true)
  })

  it('computes mediaPathSetUp based on media_path_set for local storage', () => {
    commonStore.state.storage_driver = 'local'
    commonStore.state.media_path_set = false

    const { mediaPathSetUp } = useUpload()
    expect(mediaPathSetUp.value).toBe(false)
  })

  it('queues valid files for upload', () => {
    const { queueFilesForUpload } = useUpload()

    const file = new File(['content'], 'song.mp3', { type: 'audio/mpeg' })
    const result = queueFilesForUpload([file])

    expect(result.length).toBe(1)
    expect(result[0].name).toBe('song.mp3')
  })

  it('lists a file it cannot play as skipped rather than dropping it', () => {
    const { queueFilesForUpload } = useUpload()

    const result = queueFilesForUpload([new File(['content'], 'notes.txt', { type: 'text/plain' })])

    expect(result).toEqual([])
    expect(uploadService.state.files.map(({ name, status }) => [name, status])).toContainEqual(['notes.txt', 'Skipped'])
  })

  it('counts only the uploads that are queued or still uploading', () => {
    const statuses: UploadStatus[] = [
      'Ready',
      'Uploading',
      'Retrying',
      'Processing',
      'Uploaded',
      'Canceled',
      'Errored',
      'Skipped',
    ]
    uploadService.state.files = statuses.map(status => ({ status }) as UploadFile)

    expect(useUpload().unfinishedUploadCount.value).toBe(3)
  })
})
