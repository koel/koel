import { beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playableStore } from '@/stores/playableStore'
import { offlineManifest } from '@/services/offlineManifest'
import { useOfflinePlayback } from './useOfflinePlayback'

vi.mock('@/services/offlineManifest', () => ({
  offlineManifest: {
    getAll: vi.fn().mockResolvedValue([]),
    get: vi.fn().mockResolvedValue(undefined),
    put: vi.fn().mockResolvedValue(undefined),
    remove: vi.fn().mockResolvedValue(undefined),
    clear: vi.fn().mockResolvedValue(undefined),
  },
}))

describe('useOfflinePlayback', () => {
  const h = createHarness()

  const messageListeners: Array<(event: MessageEvent) => void> = []
  const postMessageMock = vi.fn()

  // Set up the SW mock once for all tests
  Object.defineProperty(navigator, 'serviceWorker', {
    value: {
      controller: { postMessage: postMessageMock },
      addEventListener: (_event: string, handler: (event: MessageEvent) => void) => {
        messageListeners.push(handler)
      },
    },
    writable: true,
    configurable: true,
  })

  const simulateMessage = (data: Record<string, any>) => {
    messageListeners.forEach(handler => handler(new MessageEvent('message', { data })))
  }

  // Initialize the composable once to set up the listener
  const {
    makeAvailableOffline,
    removeOfflineCache,
    clearAllOfflineCache,
    isCached,
    isCaching,
    getCachingProgress,
    checkCacheStatus,
    cachedSongIds,
    cachedSongCount,
    cachingProgress,
    manifestEntries,
  } = useOfflinePlayback()

  beforeEach(() => {
    postMessageMock.mockClear()
    cachedSongIds.value.clear()
    cachingProgress.value.clear()
    manifestEntries.value = []
    vi.mocked(offlineManifest.put).mockClear()
    vi.mocked(offlineManifest.remove).mockClear()
    vi.mocked(offlineManifest.clear).mockClear()
  })

  it('sends CACHE_AUDIO message to SW', () => {
    const song = h.factory('song')
    const sourceUrl = 'http://localhost/play/abc123?t=token'
    h.mock(playableStore, 'getSourceUrl').mockReturnValue(sourceUrl)

    makeAvailableOffline(song)

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'CACHE_AUDIO',
      songId: song.id,
      sourceUrl,
    })
  })

  it('sends DELETE_AUDIO_CACHE message to SW', () => {
    const song = h.factory('song')
    const sourceUrl = 'http://localhost/play/abc123?t=token'
    h.mock(playableStore, 'getSourceUrl').mockReturnValue(sourceUrl)

    removeOfflineCache(song)

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'DELETE_AUDIO_CACHE',
      songId: song.id,
      sourceUrl,
    })
  })

  it('tracks caching progress from SW messages', () => {
    const song = h.factory('song')

    cachingProgress.value.set(song.id, 0)
    expect(isCaching(song)).toBe(true)

    simulateMessage({
      type: 'CACHE_AUDIO_PROGRESS',
      songId: song.id,
      progress: 0.5,
      received: 500,
      total: 1000,
    })

    expect(cachingProgress.value.get(song.id)).toBe(0.5)
  })

  it('marks song as cached on CACHE_AUDIO_COMPLETE and persists to manifest', () => {
    const song = h.factory('song')
    h.mock(playableStore, 'byId').mockReturnValue(song)

    cachingProgress.value.set(song.id, 0.5)

    simulateMessage({
      type: 'CACHE_AUDIO_COMPLETE',
      songId: song.id,
    })

    expect(isCached(song)).toBe(true)
    expect(cachingProgress.value.has(song.id)).toBe(false)
    expect(offlineManifest.put).toHaveBeenCalledWith(
      expect.objectContaining({
        playable: expect.objectContaining({ id: song.id }),
      }),
    )
  })

  it('removes song from cached set and manifest on DELETE_AUDIO_CACHE_COMPLETE', () => {
    const song = h.factory('song')

    cachedSongIds.value.add(song.id)
    expect(isCached(song)).toBe(true)

    simulateMessage({
      type: 'DELETE_AUDIO_CACHE_COMPLETE',
      songId: song.id,
    })

    expect(isCached(song)).toBe(false)
    expect(offlineManifest.remove).toHaveBeenCalledWith(song.id)
  })

  it('cleans up progress on CACHE_AUDIO_ERROR', () => {
    const song = h.factory('song')

    cachingProgress.value.set(song.id, 0.3)

    simulateMessage({
      type: 'CACHE_AUDIO_ERROR',
      songId: song.id,
      error: 'Network error',
    })

    expect(isCaching(song)).toBe(false)
  })

  it('sends GET_CACHE_STATUS message to SW', () => {
    const songs = [h.factory('song'), h.factory('song')]
    const urls = ['http://localhost/play/a?t=t1', 'http://localhost/play/b?t=t2']
    h.mock(playableStore, 'getSourceUrl').mockReturnValueOnce(urls[0]).mockReturnValueOnce(urls[1])

    checkCacheStatus(songs)

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'GET_CACHE_STATUS',
      sourceUrls: urls,
    })
  })

  it('returns caching progress value', () => {
    const song = h.factory('song')
    expect(getCachingProgress(song)).toBe(0)

    cachingProgress.value.set(song.id, 0.75)
    expect(getCachingProgress(song)).toBe(0.75)
  })

  it('computes cached song count', () => {
    expect(cachedSongCount.value).toBe(0)
    cachedSongIds.value.add('a')
    cachedSongIds.value.add('b')
    expect(cachedSongCount.value).toBe(2)
  })

  it('clears all offline cache', async () => {
    const song = h.factory('song')
    const sourceUrl = 'http://localhost/play/x?t=token'
    h.mock(playableStore, 'byId').mockReturnValue(song)
    h.mock(playableStore, 'getSourceUrl').mockReturnValue(sourceUrl)

    cachedSongIds.value.add(song.id)
    manifestEntries.value = [{ playable: song, cachedAt: Date.now(), size: 0 }]

    await clearAllOfflineCache()

    expect(cachedSongIds.value.size).toBe(0)
    expect(manifestEntries.value).toHaveLength(0)
    expect(offlineManifest.clear).toHaveBeenCalled()
  })
})
