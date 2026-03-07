import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import type { UploadFile } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'

const postWithProgressMock = vi.fn()

vi.mock('@/services/http', async importOriginal => {
  const actual = await importOriginal<typeof import('@/services/http')>()
  return {
    ...actual,
    postWithProgress: (...args: any[]) => postWithProgressMock(...args),
  }
})

vi.mock('@/utils/logger', () => ({
  logger: { error: vi.fn() },
}))

describe('uploadService', () => {
  const h = createHarness({
    beforeEach: () => {
      uploadService.state.files = []
    },
  })

  const createUploadFile = (overrides: Partial<UploadFile> = {}): UploadFile => ({
    id: crypto.randomUUID(),
    file: new File(['content'], 'song.mp3'),
    status: 'Ready',
    name: 'song.mp3',
    progress: 0,
    ...overrides,
  })

  it('queues files and triggers proceed', () => {
    const proceedMock = h.mock(uploadService, 'proceed')
    const file = createUploadFile()

    uploadService.queue(file)

    expect(uploadService.state.files).toHaveLength(1)
    expect(uploadService.state.files[0].id).toBe(file.id)
    expect(proceedMock).toHaveBeenCalled()
  })

  it('queues multiple files at once', () => {
    h.mock(uploadService, 'proceed')
    const files = [createUploadFile(), createUploadFile()]

    uploadService.queue(files)

    expect(uploadService.state.files).toHaveLength(2)
  })

  it('removes a file by filtering', () => {
    const proceedMock = h.mock(uploadService, 'proceed')
    const file = createUploadFile()
    uploadService.state.files = [file]

    // `remove` uses lodash `without` which needs reference equality.
    // Since state is reactive, we need to pass the reactive reference.
    uploadService.remove(uploadService.state.files[0])

    expect(uploadService.state.files).toHaveLength(0)
    expect(proceedMock).toHaveBeenCalled()
  })

  it('gets uploading files', () => {
    uploadService.state.files = [
      createUploadFile({ status: 'Uploading' }),
      createUploadFile({ status: 'Ready' }),
      createUploadFile({ status: 'Uploading' }),
    ]

    expect(uploadService.getUploadingFiles()).toHaveLength(2)
  })

  it('gets next upload candidate', () => {
    const readyFile = createUploadFile({ status: 'Ready', name: 'first.mp3' })
    uploadService.state.files = [createUploadFile({ status: 'Uploading' }), readyFile]

    const candidate = uploadService.getUploadCandidate()
    expect(candidate?.name).toBe('first.mp3')
  })

  it('returns undefined when no candidates', () => {
    uploadService.state.files = [createUploadFile({ status: 'Uploading' })]

    expect(uploadService.getUploadCandidate()).toBeUndefined()
  })

  it('respects simultaneous upload limit', () => {
    const uploadMock = h.mock(uploadService, 'upload')

    uploadService.state.files = [
      ...Array.from({ length: 5 }, () => createUploadFile({ status: 'Uploading' })),
      createUploadFile({ status: 'Ready' }),
    ]

    uploadService.proceed()

    expect(uploadMock).not.toHaveBeenCalled()
  })

  it('skips already uploading files', async () => {
    const file = createUploadFile({ status: 'Uploading' })

    await uploadService.upload(file)

    expect(postWithProgressMock).not.toHaveBeenCalled()
  })

  it('uploads a file successfully', async () => {
    const result = { song: h.factory('song'), album: h.factory('album') }
    postWithProgressMock.mockResolvedValue(result)
    const handleMock = h.mock(uploadService, 'handleUploadResult')
    const proceedMock = h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(file.status).toBe('Uploaded')
    expect(handleMock).toHaveBeenCalledWith(result)
    expect(proceedMock).toHaveBeenCalled()
  })

  it('marks file as errored if response is malformed', async () => {
    postWithProgressMock.mockResolvedValue({ message: 'The POST data is too large.' })
    const handleMock = h.mock(uploadService, 'handleUploadResult')
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(file.status).toBe('Errored')
    expect(file.message).toContain('unexpected response')
    expect(handleMock).not.toHaveBeenCalled()
  })

  it('sets progress during upload', async () => {
    postWithProgressMock.mockImplementation(async (_url: string, _data: FormData, onProgress: Function) => {
      onProgress({ loaded: 50, total: 100 })
      return null
    })
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(file.progress).toBe(50)
  })

  it('handles upload error with message', async () => {
    const error = Object.assign(new Error('Upload failed with status 413'), {
      responseData: { message: 'File too large' },
    })

    postWithProgressMock.mockRejectedValue(error)
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(file.status).toBe('Errored')
    expect(file.message).toContain('File too large')
  })

  it('handles upload error without message', async () => {
    postWithProgressMock.mockRejectedValue(new Error('network error'))
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(file.status).toBe('Errored')
    expect(file.message).toBe('Upload failed: Unknown error.')
  })

  it('retries a file', () => {
    const proceedMock = h.mock(uploadService, 'proceed')
    const file = createUploadFile({ status: 'Errored', progress: 50 })

    uploadService.retry(file)

    expect(file.status).toBe('Ready')
    expect(file.progress).toBe(0)
    expect(proceedMock).toHaveBeenCalled()
  })

  it('retries all files', () => {
    const proceedMock = h.mock(uploadService, 'proceed')
    const files = [createUploadFile({ status: 'Errored' }), createUploadFile({ status: 'Errored' })]
    uploadService.state.files = files

    uploadService.retryAll()

    expect(files.every(f => f.status === 'Ready')).toBe(true)
    expect(proceedMock).toHaveBeenCalled()
  })

  it('removes failed files', () => {
    uploadService.state.files = [
      createUploadFile({ status: 'Errored' }),
      createUploadFile({ status: 'Ready' }),
      createUploadFile({ status: 'Errored' }),
    ]

    uploadService.removeFailed()

    expect(uploadService.state.files).toHaveLength(1)
    expect(uploadService.state.files[0].status).toBe('Ready')
  })

  it('warns upon window unload when files exist', () => {
    uploadService.state.files = [createUploadFile()]
    expect(uploadService.shouldWarnUponWindowUnload()).toBe(true)
  })

  it('does not warn when no files', () => {
    uploadService.state.files = []
    expect(uploadService.shouldWarnUponWindowUnload()).toBe(false)
  })

  it('resets a file', () => {
    const file = createUploadFile({ status: 'Errored', progress: 75 })

    uploadService.resetFile(file)

    expect(file.status).toBe('Ready')
    expect(file.progress).toBe(0)
  })
})
