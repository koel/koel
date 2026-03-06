import { describe, expect, it, vi } from 'vitest'
import { commonStore } from '@/stores/commonStore'

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
})
