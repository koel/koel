import { describe, expect, it, vi } from 'vitest'
import { ref, computed } from 'vue'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { DialogBoxStub, MessageToasterStub } from '@/__tests__/stubs'
import { playableStore } from '@/stores/playableStore'

const cachedSongIds = ref(new Set<string>())
const clearAllOfflineCacheMock = vi.fn().mockResolvedValue(undefined)

vi.mock('@/composables/useOfflinePlayback', () => ({
  useOfflinePlayback: () => ({
    swReady: ref(true),
    cachedSongIds,
    manifestEntries: ref([]),
    storageUsage: ref(500 * 1024 * 1024),
    storageQuota: ref(2 * 1024 * 1024 * 1024),
    cachedSongCount: computed(() => cachedSongIds.value.size),
    removeOfflineCache: vi.fn(),
    clearAllOfflineCache: clearAllOfflineCacheMock,
    isCached: vi.fn().mockReturnValue(false),
  }),
}))

// Mock service worker controller
Object.defineProperty(navigator, 'serviceWorker', {
  value: { controller: { postMessage: vi.fn() }, addEventListener: vi.fn() },
  writable: true,
  configurable: true,
})

import Component from './OfflineStorage.vue'

describe('offlineStorage.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      cachedSongIds.value = new Set<string>()
      clearAllOfflineCacheMock.mockClear()
    },
  })

  it('shows storage usage info', () => {
    h.render(Component)
    screen.getByText('500.0 MB / 2.0 GB')
    screen.getByText('0 songs available offline')
  })

  it('shows cached songs count', () => {
    const songs = h.factory('song', 3)
    songs.forEach(s => {
      cachedSongIds.value.add(s.id)
      h.mock(playableStore, 'byId').mockImplementation((id: string) => songs.find(song => song.id === id))
    })

    h.render(Component)
    screen.getByText('3 songs available offline')
  })

  it('clears all offline songs with confirmation', async () => {
    const song = h.factory('song')
    cachedSongIds.value.add(song.id)
    h.mock(playableStore, 'byId').mockReturnValue(song)

    const confirmMock = h.mock(DialogBoxStub.value, 'confirm', true)
    h.mock(MessageToasterStub.value, 'success')

    h.render(Component)

    await h.user.click(screen.getByText('Clear All'))

    await waitFor(() => {
      expect(confirmMock).toHaveBeenCalled()
      expect(clearAllOfflineCacheMock).toHaveBeenCalled()
    })
  })

  it('does not show cached songs section when empty', () => {
    h.render(Component)
    expect(screen.queryByText('Cached Songs')).toBeNull()
    expect(screen.queryByText('Clear All')).toBeNull()
  })
})
