import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { eventBus } from '@/utils/eventBus'

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
    const infoMock = h.mock(MessageToasterStub.value, 'info')
    h.render(Component)

    expect(infoMock).not.toHaveBeenCalled()
    expect(isNewerVersionDeployedMock).not.toHaveBeenCalled()
  })

  it('offers a reload when a file fails to load because a newer version is deployed', async () => {
    isNewerVersionDeployedMock.mockResolvedValue(true)
    const infoMock = h.mock(MessageToasterStub.value, 'info')
    h.render(Component)

    failToLoadAFile()
    await vi.waitFor(() => expect(infoMock).toHaveBeenCalled())

    const [reload] = infoMock.mock.calls[0][0].actions
    reload.handler()

    expect(reload.primary).toBe(true)
    expect(forceReloadWindowMock).toHaveBeenCalled()
  })

  it('stays hidden when a file fails to load for another reason', async () => {
    isNewerVersionDeployedMock.mockResolvedValue(false)
    const infoMock = h.mock(MessageToasterStub.value, 'info')
    h.render(Component)

    failToLoadAFile()
    await vi.waitFor(() => expect(isNewerVersionDeployedMock).toHaveBeenCalled())

    expect(infoMock).not.toHaveBeenCalled()
  })

  it('offers a reload once when the API reports a newer build more than once', () => {
    const infoMock = h.mock(MessageToasterStub.value, 'info')
    h.render(Component)

    eventBus.emit('NEW_VERSION_DEPLOYED')
    eventBus.emit('NEW_VERSION_DEPLOYED')

    expect(infoMock).toHaveBeenCalledOnce()
  })
})
