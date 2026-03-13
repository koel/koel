import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { authService } from '@/services/authService'
import Component from './GlobalEventListeners.vue'

const goMock = vi.fn()

vi.mock('@/composables/useRouter', () => ({
  useRouter: () => ({
    go: goMock,
  }),
}))

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  forceReloadWindow: vi.fn(),
}))

describe('globalEventListeners.vue', () => {
  const h = createHarness()

  it('logs out on LOG_OUT event', async () => {
    const logoutMock = h.mock(authService, 'logout').mockResolvedValue(undefined)

    h.render(Component)
    await eventBus.emit('LOG_OUT')

    expect(logoutMock).toHaveBeenCalled()
    expect(goMock).toHaveBeenCalledWith('/')
  })
})
