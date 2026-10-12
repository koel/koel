import { screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { zipDownloadService } from '@/services/zipDownloadService'
import { eventBus } from '@/utils/eventBus'
import Component from './ZipDownloadButton.vue'

const mockShowConfirmDialog = vi.fn()

vi.mock('@/composables/useDialogBox', () => ({
  useDialogBox: () => ({
    showConfirmDialog: mockShowConfirmDialog,
  }),
}))

describe('zipDownloadButton.vue', () => {
  const h = createHarness({
    beforeEach: () => zipDownloadService.dismiss(),
  })

  it('stays hidden while nothing is being zipped', () => {
    h.render(Component)

    expect(screen.queryByTestId('zip-download-button')).toBeNull()
  })

  it('fills the ring as the songs arrive', () => {
    Object.assign(zipDownloadService.state, { status: 'zipping', bytesDone: 25, bytesTotal: 100 })
    h.render(Component)

    expect(screen.getByRole('progressbar').getAttribute('aria-valuenow')).toBe('25')
  })

  it('shows its tooltip for a few seconds when zipping starts', async () => {
    vi.useFakeTimers()
    const dispatchSpy = vi.spyOn(HTMLElement.prototype, 'dispatchEvent')
    const dispatchedTypes = () => dispatchSpy.mock.calls.map(([event]) => event.type)
    h.render(Component, { global: { stubs: { transition: false } } })

    zipDownloadService.state.status = 'zipping'
    await vi.advanceTimersByTimeAsync(50)
    expect(dispatchedTypes()).toContain('mouseenter')
    expect(dispatchedTypes()).not.toContain('mouseleave')

    await vi.advanceTimersByTimeAsync(3_000)
    expect(dispatchedTypes()).toContain('mouseleave')
    vi.useRealTimers()
  })

  it('announces a saved archive', () => {
    const toastMock = h.mock(MessageToasterStub.value, 'success')
    h.render(Component)

    eventBus.emit('DOWNLOAD_ARCHIVE_SAVED')

    expect(toastMock).toHaveBeenCalled()
  })

  it('cancels after the user confirms', async () => {
    zipDownloadService.state.status = 'zipping'
    mockShowConfirmDialog.mockResolvedValue(true)
    const cancelMock = h.mock(zipDownloadService, 'cancel')
    h.render(Component)

    await h.user.click(screen.getByTestId('zip-download-button'))

    expect(cancelMock).toHaveBeenCalled()
  })

  it('saves a ready archive', async () => {
    zipDownloadService.state.status = 'ready'
    const saveMock = h.mock(zipDownloadService, 'save')
    h.render(Component)

    await h.user.click(screen.getByTestId('zip-download-button'))

    expect(saveMock).toHaveBeenCalled()
  })

  it('dismisses a failure', async () => {
    zipDownloadService.state.status = 'failed'
    const dismissMock = h.mock(zipDownloadService, 'dismiss')
    h.render(Component)

    await h.user.click(screen.getByTestId('zip-download-button'))

    expect(dismissMock).toHaveBeenCalled()
  })
})
