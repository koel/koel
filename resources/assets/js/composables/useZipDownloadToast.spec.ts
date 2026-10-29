import { screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { defineComponent, ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterKey } from '@/config/symbols'
import { zipDownloadService } from '@/services/zipDownloadService'
import { useZipDownloadToast } from './useZipDownloadToast'

import MessageToaster from '@/components/ui/message-toaster/MessageToaster.vue'

const mockShowConfirmDialog = vi.fn()

vi.mock('@/composables/useDialogBox', () => ({
  useDialogBox: () => ({
    showConfirmDialog: mockShowConfirmDialog,
  }),
}))

describe('useZipDownloadToast', () => {
  const h = createHarness({
    beforeEach: () => zipDownloadService.dismiss(),
  })

  const { state } = zipDownloadService
  const toaster = ref<InstanceType<typeof MessageToaster>>()

  const renderToaster = async () => {
    const ZipDownloadToastUser = defineComponent({
      setup: () => useZipDownloadToast(),
      template: '<div />',
    })

    h.render(
      defineComponent({
        components: {
          MessageToaster,
          ZipDownloadToastUser,
        },
        setup: () => ({ toaster }),
        template: '<MessageToaster ref="toaster" /><ZipDownloadToastUser v-if="toaster" />',
      }),
      { global: { provide: { [MessageToasterKey as symbol]: toaster } } },
    )

    await h.tick()
  }

  const startZipping = async () => {
    let finishBuilding!: () => void
    zipDownloadService.building = new Promise(resolve => (finishBuilding = resolve))
    Object.assign(state, {
      status: 'zipping',
      bytesDone: 25,
      bytesTotal: 100,
    })
    await h.tick()

    return async (status: 'ready' | 'failed' | 'idle', error = '') => {
      Object.assign(state, {
        status,
        error,
      })
      finishBuilding()
      await h.tick(3)
    }
  }

  const getToast = () => screen.getByRole('article')

  it('shows the progress in a toast that cannot be dismissed while zipping', async () => {
    await renderToaster()
    await startZipping()

    expect(screen.getByRole('progressbar').getAttribute('aria-valuenow')).toBe('25')
    expect(screen.queryByRole('button', { name: 'Dismiss' })).toBeNull()

    state.bytesDone = 50
    await h.tick()

    expect(screen.getByRole('progressbar').getAttribute('aria-valuenow')).toBe('50')
  })

  it('cancels after the user confirms', async () => {
    mockShowConfirmDialog.mockResolvedValue(true)
    const cancelMock = h.mock(zipDownloadService, 'cancel')
    await renderToaster()
    await startZipping()

    await h.user.click(screen.getByRole('button', { name: 'Cancel' }))

    expect(cancelMock).toHaveBeenCalled()
  })

  it('keeps zipping when the user does not confirm the cancellation', async () => {
    mockShowConfirmDialog.mockResolvedValue(false)
    const cancelMock = h.mock(zipDownloadService, 'cancel')
    await renderToaster()
    await startZipping()

    await h.user.click(screen.getByRole('button', { name: 'Cancel' }))

    expect(cancelMock).not.toHaveBeenCalled()
  })

  it('saves a ready archive from the toast and closes it', async () => {
    const saveMock = h.mock(zipDownloadService, 'save')
    await renderToaster()
    const finish = await startZipping()

    await finish('ready')
    expect(getToast().classList).toContain('success')

    await h.user.click(screen.getByRole('button', { name: 'Save' }))
    await h.tick()

    expect(saveMock).toHaveBeenCalled()
    expect(screen.queryByRole('article')).toBeNull()
  })

  it('discards an unsaved archive when its toast is dismissed', async () => {
    const dismissMock = h.mock(zipDownloadService, 'dismiss')
    await renderToaster()
    const finish = await startZipping()

    await finish('ready')
    await h.user.click(screen.getByRole('button', { name: 'Dismiss' }))

    expect(dismissMock).toHaveBeenCalled()
  })

  it('shows the error of a failed download', async () => {
    await renderToaster()
    const finish = await startZipping()

    await finish('failed', 'Song unavailable')

    expect(getToast().classList).toContain('danger')
    expect(getToast().textContent).toContain('Song unavailable')
  })

  it('shows a canceled download as a warning', async () => {
    await renderToaster()
    const finish = await startZipping()

    await finish('idle')

    expect(getToast().classList).toContain('warning')
  })

  it('replaces the toast of an earlier archive when a new one starts', async () => {
    await renderToaster()
    const finish = await startZipping()
    await finish('ready')

    await startZipping()

    expect(screen.getAllByRole('article')).toHaveLength(1)
    expect(getToast().classList).toContain('loading')
  })
})
