import { describe, expect, it, vi } from 'vite-plus/test'
import { ref } from 'vue'
import { waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './AppInitializer.vue'

vi.mock('@/services/socketService', () => ({
  socketService: {
    init: vi.fn().mockResolvedValue(false),
  },
}))

vi.mock('@/services/socketListener', () => ({
  socketListener: {
    listen: vi.fn(),
  },
}))

vi.mock('@/services/broadcastSubscriber', () => ({
  broadcastSubscriber: {
    init: vi.fn(),
  },
}))

vi.mock('@/composables/useErrorHandler', () => ({
  useErrorHandler: () => ({
    handleHttpError: vi.fn(),
  }),
}))

vi.mock('@/composables/useOverlay', () => ({
  useOverlay: () => ({
    showOverlay: vi.fn(),
    hideOverlay: vi.fn(),
  }),
}))

vi.mock('@/composables/useAuthorization', () => ({
  useAuthorization: () => ({
    currentUser: ref({ id: '1', email: 'test@test.com' }),
  }),
}))

describe('appInitializer.vue', () => {
  const h = createHarness()

  it('emits success after init', async () => {
    h.mock(commonStore, 'init').mockResolvedValue(undefined)

    const { emitted } = h.render(Component)

    await waitFor(() => {
      expect(commonStore.init).toHaveBeenCalled()
      expect(emitted().success).toBeTruthy()
    })
  })

  it('emits error when init fails', async () => {
    const error = new Error('Init failed')
    h.mock(commonStore, 'init').mockRejectedValue(error)

    const { emitted } = h.render(Component)

    await waitFor(() => {
      expect(emitted().error).toBeTruthy()
    })
  })
})
