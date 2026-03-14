import { describe, it, vi } from 'vite-plus/test'
import { ref, computed } from 'vue'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playableStore } from '@/stores/playableStore'

const cachedSongIds = ref(new Set<string>())

vi.mock('@/composables/useOfflinePlayback', () => ({
  useOfflinePlayback: () => ({
    swReady: ref(true),
    cachedSongIds,
    manifestEntries: ref([]),
    storageUsage: ref(0),
    storageQuota: ref(0),
    cachedSongCount: computed(() => cachedSongIds.value.size),
    makeAvailableOffline: vi.fn(),
    removeOfflineCache: vi.fn(),
    clearAllOfflineCache: vi.fn(),
    isCached: (p: Playable) => cachedSongIds.value.has(p.id),
    isCaching: vi.fn().mockReturnValue(false),
    getCachingProgress: vi.fn().mockReturnValue(0),
    checkCacheStatus: vi.fn(),
    refreshStorageEstimate: vi.fn(),
  }),
}))

import Component from './OfflineSongsScreen.vue'

describe('offlineSongsScreen', () => {
  const h = createHarness({
    beforeEach: () => {
      cachedSongIds.value = new Set<string>()
    },
  })

  it('shows empty state when no songs are cached', () => {
    h.render(Component)
    screen.getByText('No songs available offline.')
  })

  it('shows cached songs count in header', () => {
    const songs = h.factory('song', 3)
    songs.forEach(s => {
      cachedSongIds.value.add(s.id)
    })
    h.mock(playableStore, 'byId').mockImplementation((id: string) => songs.find(song => song.id === id))

    h.render(Component)
    screen.getByText('3 songs')
  })
})
