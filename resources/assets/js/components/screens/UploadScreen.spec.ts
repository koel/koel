import { describe, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './UploadScreen.vue'

vi.mock('@/utils/mediaHelper', () => ({
  acceptedExtensions: ['mp3', 'flac', 'ogg'],
}))

vi.mock('@/composables/useUpload', () => ({
  useUpload: () => ({
    allowsUpload: { value: true },
    mediaPathSetUp: { value: true },
    queueFilesForUpload: vi.fn(),
    handleDropEvent: vi.fn(),
  }),
}))

describe('uploadScreen.vue', () => {
  const h = createHarness()

  it('renders upload header', () => {
    h.render(Component)
    screen.getByText('Upload Media')
  })
})
