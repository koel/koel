import { describe, expect, it, vi } from 'vite-plus/test'
import { nextTick } from 'vue'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'

const isNewerVersionDeployedMock = vi.fn<() => Promise<boolean>>()
const forceReloadWindowMock = vi.fn()

vi.mock('@/utils/deployment', () => ({
  isNewerVersionDeployed: () => isNewerVersionDeployedMock(),
}))

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  forceReloadWindow: () => forceReloadWindowMock(),
}))

import Component from './UpdateNotification.vue'

describe('updateNotification', () => {
  const h = createHarness()

  const failToLoadAFile = () => window.dispatchEvent(new Event('vite:preloadError'))

  it('stays hidden until a file fails to load', () => {
    h.render(Component)

    expect(screen.queryByText('Koel has been updated.')).toBeNull()
    expect(isNewerVersionDeployedMock).not.toHaveBeenCalled()
  })

  it('offers a reload when a file fails to load because a newer version is deployed', async () => {
    isNewerVersionDeployedMock.mockResolvedValue(true)
    h.render(Component)

    failToLoadAFile()
    await screen.findByText('Koel has been updated.')
    await h.user.click(screen.getByRole('button', { name: 'Reload' }))

    expect(forceReloadWindowMock).toHaveBeenCalled()
  })

  it('stays hidden when a file fails to load for another reason', async () => {
    isNewerVersionDeployedMock.mockResolvedValue(false)
    h.render(Component)

    failToLoadAFile()
    await vi.waitFor(() => expect(isNewerVersionDeployedMock).toHaveBeenCalled())

    expect(screen.queryByText('Koel has been updated.')).toBeNull()
  })

  it('keeps the notice once a newer version is confirmed, even if a later check says otherwise', async () => {
    let confirmNewerVersion: (deployed: boolean) => void = () => {}
    let denyNewerVersion: (deployed: boolean) => void = () => {}

    isNewerVersionDeployedMock
      .mockImplementationOnce(() => new Promise(resolve => (confirmNewerVersion = resolve)))
      .mockImplementationOnce(() => new Promise(resolve => (denyNewerVersion = resolve)))

    h.render(Component)

    failToLoadAFile()
    failToLoadAFile()
    confirmNewerVersion(true)
    await screen.findByText('Koel has been updated.')

    denyNewerVersion(false)
    await new Promise(resolve => setTimeout(resolve))
    await nextTick()

    screen.getByText('Koel has been updated.')
  })
})
