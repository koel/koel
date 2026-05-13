import { describe, expect, it, vi } from 'vite-plus/test'
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
      uploadService.abortHandles.clear()
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

  const mockPostWithProgress = (resolveValue: any) => {
    postWithProgressMock.mockReturnValue({
      promise: Promise.resolve(resolveValue),
      abort: vi.fn(),
    })
  }

  const mockPostWithProgressRejection = (error: any) => {
    postWithProgressMock.mockReturnValue({
      promise: Promise.reject(error),
      abort: vi.fn(),
    })
  }

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
    const result = { song: h.factory('song').make(), album: h.factory('album').make() }
    mockPostWithProgress(result)
    const handleMock = h.mock(uploadService, 'handleUploadResult')
    const proceedMock = h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(file.status).toBe('Uploaded')
    expect(handleMock).toHaveBeenCalledWith(result)
    expect(proceedMock).toHaveBeenCalled()
  })

  it('marks file as errored if response is malformed', async () => {
    mockPostWithProgress({ message: 'The POST data is too large.' })
    const handleMock = h.mock(uploadService, 'handleUploadResult')
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(file.status).toBe('Errored')
    expect(file.message).toContain('unexpected response')
    expect(handleMock).not.toHaveBeenCalled()
  })

  it('sets progress during upload', async () => {
    const result = { song: h.factory('song').make(), album: h.factory('album').make() }
    postWithProgressMock.mockImplementation((_url: string, _data: FormData, onProgress: Function) => {
      onProgress({ loaded: 50, total: 100 })
      return { promise: Promise.resolve(result), abort: vi.fn() }
    })
    h.mock(uploadService, 'handleUploadResult')
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(file.progress).toBe(50)
    expect(file.status).toBe('Uploaded')
  })

  it('handles upload error with message', async () => {
    const error = Object.assign(new Error('Upload failed with status 413'), {
      responseData: { message: 'File too large' },
    })

    mockPostWithProgressRejection(error)
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(file.status).toBe('Errored')
    expect(file.message).toContain('File too large')
  })

  it('handles upload error without message', async () => {
    mockPostWithProgressRejection(new Error('network error'))
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(file.status).toBe('Errored')
    expect(file.message).toBe('Server error.')
  })

  it('shows a generic server error when responseData cannot be parsed', async () => {
    const error = Object.assign(new Error('Upload failed with status 413'), {
      status: 413,
      responseData: undefined,
    })

    mockPostWithProgressRejection(error)
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(file.status).toBe('Errored')
    expect(file.message).toBe('Server error.')
  })

  it('aborts an in-progress upload', async () => {
    const abortMock = vi.fn()
    postWithProgressMock.mockReturnValue({
      promise: new Promise((_, reject) => {
        // Simulate abort: when abort is called, reject with AbortError
        abortMock.mockImplementation(() => reject(new DOMException('Upload aborted', 'AbortError')))
      }),
      abort: (...args: any[]) => abortMock(...args),
    })
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    const uploadPromise = uploadService.upload(file)

    expect(file.status).toBe('Uploading')
    expect(uploadService.abortHandles.has(file.id)).toBe(true)

    uploadService.abort(file)

    await uploadPromise

    expect(file.status).toBe('Canceled')
    expect(abortMock).toHaveBeenCalled()
  })

  it('cleans up abort handle after successful upload', async () => {
    const result = { song: h.factory('song').make(), album: h.factory('album').make() }
    mockPostWithProgress(result)
    h.mock(uploadService, 'handleUploadResult')
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(uploadService.abortHandles.has(file.id)).toBe(false)
  })

  it('cleans up abort handle on remove', () => {
    h.mock(uploadService, 'proceed')
    const file = createUploadFile()
    uploadService.state.files = [file]
    uploadService.abortHandles.set(file.id, vi.fn())

    uploadService.remove(uploadService.state.files[0])

    expect(uploadService.abortHandles.has(file.id)).toBe(false)
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
