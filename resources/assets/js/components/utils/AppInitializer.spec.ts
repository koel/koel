import { describe, expect, it, vi } from 'vite-plus/test'
import { ref } from 'vue'
import { waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { initializeOfflinePlayback } from '@/composables/useOfflinePlayback'
import Component from './AppInitializer.vue'

vi.mock('@/composables/useOfflinePlayback', () => ({
  initializeOfflinePlayback: vi.fn().mockResolvedValue(undefined),
  shouldWarnUponWindowUnload: vi.fn().mockReturnValue(false),
}))

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

  it('waits for the offline manifest before emitting success', async () => {
    let resolveManifest!: () => void
    const manifestReady = new Promise<void>(resolve => (resolveManifest = resolve))
    h.mock(commonStore, 'init').mockResolvedValue(undefined)
    vi.mocked(initializeOfflinePlayback).mockReturnValueOnce(manifestReady)

    const { emitted } = h.render(Component)

    await waitFor(() => expect(initializeOfflinePlayback).toHaveBeenCalled())
    expect(emitted().success).toBeUndefined()

    resolveManifest()
    await waitFor(() => expect(emitted().success).toBeTruthy())
  })

  it('starts offline initialization while common data is still loading', async () => {
    let resolveCommonData!: () => void
    const commonDataReady = new Promise<void>(resolve => {
      resolveCommonData = resolve
    })
    h.mock(commonStore, 'init').mockReturnValue(commonDataReady)

    const { emitted } = h.render(Component)

    await waitFor(() => expect(commonStore.init).toHaveBeenCalled())
    expect(initializeOfflinePlayback).toHaveBeenCalled()
    expect(emitted().success).toBeUndefined()

    resolveCommonData()
    await waitFor(() => expect(emitted().success).toBeTruthy())
  })

  it('continues initialization when the offline manifest cannot be loaded', async () => {
    h.mock(commonStore, 'init').mockResolvedValue(undefined)
    vi.mocked(initializeOfflinePlayback).mockRejectedValueOnce(new Error('IndexedDB unavailable'))

    const { emitted } = h.render(Component)

    await waitFor(() => expect(emitted().success).toBeTruthy())
    expect(emitted().error).toBeUndefined()
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
