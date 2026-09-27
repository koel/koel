import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { zipDownloadService } from '@/services/zipDownloadService'
import Component from './ZipDownloadPanel.vue'

describe('zipDownloadPanel.vue', () => {
  const h = createHarness({
    beforeEach: () => zipDownloadService.dismiss(),
  })

  it('stays hidden while nothing is being zipped', () => {
    h.render(Component)

    expect(screen.queryByTestId('zip-download-panel')).toBeNull()
  })

  it('lets the user cancel while zipping', async () => {
    zipDownloadService.state.status = 'zipping'
    const cancelMock = h.mock(zipDownloadService, 'cancel')
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Cancel' }))

    expect(cancelMock).toHaveBeenCalled()
  })

  it('offers to save a ready archive', async () => {
    zipDownloadService.state.status = 'ready'
    const saveMock = h.mock(zipDownloadService, 'save')
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(saveMock).toHaveBeenCalled()
  })

  it('lets the user dismiss a failure', async () => {
    zipDownloadService.state.status = 'failed'
    const dismissMock = h.mock(zipDownloadService, 'dismiss')
    h.render(Component)

    await h.user.click(screen.getByTitle('Dismiss'))

    expect(dismissMock).toHaveBeenCalled()
  })
})
