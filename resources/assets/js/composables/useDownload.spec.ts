import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { downloadService } from '@/services/downloadService'
import { ZipTooLargeError } from '@/services/zipDownloadService'
import { useDownload } from './useDownload'

const handleHttpErrorMock = vi.fn()

vi.mock('@/composables/useErrorHandler', () => ({
  useErrorHandler: () => ({ handleHttpError: handleHttpErrorMock }),
}))

describe('useDownload', () => {
  const h = createHarness({
    beforeEach: () => handleHttpErrorMock.mockClear(),
  })

  const setUp = () => {
    let download: ReturnType<typeof useDownload> | null = null

    h.render({
      template: '<div />',
      setup: () => {
        download = useDownload()
      },
    })

    return download!
  }

  it('shows a zip problem as its own message', async () => {
    const toastMock = h.mock(MessageToasterStub.value, 'error')
    h.mock(downloadService, 'fromAlbum').mockRejectedValue(new ZipTooLargeError('Too big'))

    await setUp().fromAlbum(h.factory('album').make())

    expect(toastMock).toHaveBeenCalledWith('Too big')
    expect(handleHttpErrorMock).not.toHaveBeenCalled()
  })

  it('hands any other failure to the error handler', async () => {
    const failure = new Error('Network down')
    h.mock(downloadService, 'fromArtist').mockRejectedValue(failure)

    await setUp().fromArtist(h.factory('artist').make())

    expect(handleHttpErrorMock).toHaveBeenCalledWith(failure)
  })
})
