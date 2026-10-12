import { screen } from '@testing-library/vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import type { UploadFile, UploadStatus } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'
import Component from './UploadSummary.vue'

const makeFile = (status: UploadStatus, progress = 0): UploadFile => ({
  id: crypto.randomUUID(),
  file: new File([new Uint8Array(1000)], 'song.mp3'),
  status,
  name: 'song.mp3',
  progress,
})

describe('uploadSummary.vue', () => {
  const h = createHarness()

  beforeEach(() => vi.useFakeTimers())
  afterEach(() => vi.useRealTimers())

  it('shows how much of the batch is sent', () => {
    uploadService.state.files = [
      makeFile('Uploaded'),
      makeFile('Uploading', 50),
      makeFile('Errored'),
      makeFile('Skipped'),
    ]

    h.render(Component)

    expect((screen.getByTestId('upload-progress') as HTMLProgressElement).value).toBe(1500)
    expect((screen.getByTestId('upload-progress') as HTMLProgressElement).max).toBe(2000)
  })

  it('estimates the time left from the upload speed', async () => {
    uploadService.state.files = [makeFile('Uploading', 0)]

    h.render(Component)

    uploadService.state.files[0].progress = 10
    await vi.advanceTimersByTimeAsync(1000)

    expect(screen.getByTestId('time-left').dataset.seconds).toBe('9')
  })
})
