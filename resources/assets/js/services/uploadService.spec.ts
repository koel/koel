import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import type { UploadFile } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'

const postWithProgressMock = vi.fn()
const putToStorageMock = vi.fn()
const postJsonMock = vi.fn()

vi.mock('@/services/http', async importOriginal => {
  const actual = await importOriginal<typeof import('@/services/http')>()
  return {
    ...actual,
    postWithProgress: (...args: any[]) => postWithProgressMock(...args),
  }
})

vi.mock('@/services/httpUpload', async importOriginal => {
  const actual = await importOriginal<typeof import('@/services/httpUpload')>()
  return {
    ...actual,
    putToStorageWithProgress: (...args: any[]) => putToStorageMock(...args),
    postJson: (...args: any[]) => postJsonMock(...args),
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
      commonStore.state.supports_presigned_uploads = false
      postWithProgressMock.mockClear()
      putToStorageMock.mockClear()
      postJsonMock.mockReset()
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

  const mockPostWithProgress = (data: any, status = 200) => {
    postWithProgressMock.mockReturnValue({
      promise: Promise.resolve({ status, data }),
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

  it('finishes a keyless queued upload rather than stranding it', async () => {
    mockPostWithProgress(null, 202)
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(file.status).toBe('Uploaded')
  })

  it('leaves a queued upload processing until the broadcast resolves it', async () => {
    mockPostWithProgress(null, 202)
    const handleMock = h.mock(uploadService, 'handleUploadResult')
    h.mock(uploadService, 'proceed')

    const file = createUploadFile({ uploadKey: '1__abc__song.mp3' })
    await uploadService.upload(file)

    expect(file.status).toBe('Processing')
    expect(handleMock).not.toHaveBeenCalled()
  })

  it('keeps a file errored when its failure broadcast beats the 202', async () => {
    mockPostWithProgress(null, 202)
    h.mock(uploadService, 'proceed')

    const file = createUploadFile({ uploadKey: '1__abc__song.mp3' })
    uploadService.state.files = [file]

    const uploading = uploadService.upload(file)
    uploadService.handleUploadFailure({ upload_key: '1__abc__song.mp3', message: 'Empty file' })
    await uploading

    expect(file.status).toBe('Errored')
  })

  it('does not count a processing file against the upload slots', () => {
    uploadService.state.files = [createUploadFile({ status: 'Processing' }), createUploadFile({ status: 'Uploading' })]

    expect(uploadService.getUploadingFiles()).toHaveLength(1)
  })

  it('completes a processing file when its broadcast arrives', () => {
    const file = createUploadFile({ status: 'Processing', uploadKey: '1__abc__song.mp3' })
    uploadService.state.files = [file]

    uploadService.handleUploadResult({
      song: h.factory('song').make(),
      album: h.factory('album').make(),
      upload_key: '1__abc__song.mp3',
    })

    expect(file.status).toBe('Uploaded')
  })

  it('errors a processing file when its upload fails server-side', () => {
    const file = createUploadFile({ status: 'Processing', uploadKey: '1__abc__song.mp3' })
    uploadService.state.files = [file]
    h.mock(uploadService, 'proceed')

    uploadService.handleUploadFailure({ upload_key: '1__abc__song.mp3', message: 'Empty file' })

    expect(file.status).toBe('Errored')
    expect(file.message).toBe('Upload failed: Empty file')
  })

  it('ignores a broadcast for a file it no longer has', () => {
    uploadService.state.files = []

    expect(() => uploadService.handleUploadFailure({ upload_key: 'gone', message: 'Empty file' })).not.toThrow()
  })

  it('sends the file straight to storage when the server presigns uploads', async () => {
    commonStore.state.supports_presigned_uploads = true

    const presigned = {
      key: '1__abc__song.mp3',
      url: 'https://bucket.example.com/1__abc__song.mp3?signature=xyz',
      headers: { 'Content-Type': 'audio/mpeg' },
      expires_at: '2099-01-01T00:00:00+00:00',
    }

    postJsonMock
      .mockReturnValueOnce({ promise: Promise.resolve({ status: 200, data: presigned }), abort: vi.fn() })
      .mockReturnValueOnce({ promise: Promise.resolve({ status: 202, data: null }), abort: vi.fn() })
    putToStorageMock.mockReturnValue({ promise: Promise.resolve({ status: 200, data: null }), abort: vi.fn() })
    const handleMock = h.mock(uploadService, 'handleUploadResult')
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    await uploadService.upload(file)

    expect(postJsonMock).toHaveBeenNthCalledWith(1, 'upload/presign', { file_name: 'song.mp3' })
    expect(putToStorageMock).toHaveBeenCalledWith(presigned.url, file.file, presigned.headers, expect.any(Function))
    expect(postJsonMock).toHaveBeenNthCalledWith(2, 'upload/complete', { key: presigned.key })
    expect(postWithProgressMock).not.toHaveBeenCalled()
    expect(file.status).toBe('Processing')
    expect(file.uploadKey).toBe(presigned.key)
    expect(handleMock).not.toHaveBeenCalled()
  })

  it('sets progress during upload', async () => {
    const result = { song: h.factory('song').make(), album: h.factory('album').make() }
    postWithProgressMock.mockImplementation((_url: string, _data: FormData, onProgress: Function) => {
      onProgress({ loaded: 50, total: 100 })
      return { promise: Promise.resolve({ status: 200, data: result }), abort: vi.fn() }
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

  it('aborts a presigned upload while it is still being presigned', async () => {
    commonStore.state.supports_presigned_uploads = true

    const abortMock = vi.fn()
    postJsonMock.mockReturnValue({
      promise: new Promise((_, reject) => {
        abortMock.mockImplementation(() => reject(new DOMException('Upload aborted', 'AbortError')))
      }),
      abort: (...args: any[]) => abortMock(...args),
    })
    h.mock(uploadService, 'proceed')

    const file = createUploadFile()
    const uploadPromise = uploadService.upload(file)

    expect(uploadService.abortHandles.has(file.id)).toBe(true)

    uploadService.abort(file)
    await uploadPromise

    expect(abortMock).toHaveBeenCalled()
    expect(putToStorageMock).not.toHaveBeenCalled()
    expect(file.status).toBe('Canceled')
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

  it('invalidates album and artist song caches when handling an upload result', () => {
    const song = h.factory('song').make()
    const album = h.factory('album').make()
    const invalidateMock = h.mock(playableStore, 'invalidateAlbumAndArtistSongCaches')
    h.mock(playableStore, 'syncWithVault')
    h.mock(albumStore, 'syncWithVault')

    uploadService.handleUploadResult({ song, album })

    expect(invalidateMock).toHaveBeenCalledWith(song)
  })
})
