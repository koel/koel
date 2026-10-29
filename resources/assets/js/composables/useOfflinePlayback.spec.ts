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

const toastPromiseMock = vi.fn()

vi.mock('@/composables/useMessageToaster', () => ({
  useMessageToaster: () => ({
    toastPromise: toastPromiseMock,
  }),
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
    hasCachingError,
    getCachingProgress,
    checkCacheStatus,
    cachedSongIds,
    cachedSongCount,
    cachingProgress,
    manifestEntries,
    togglePlayablesOffline,
    hasOfflineCapablePlayables,
  } = useOfflinePlayback()

  beforeEach(() => {
    postMessageMock.mockClear()
    toastPromiseMock.mockClear()
    cachedSongIds.value.clear()
    cachingProgress.value.clear()
    manifestEntries.value = []
    vi.mocked(offlineManifest.put).mockClear()
    vi.mocked(offlineManifest.remove).mockClear()
    vi.mocked(offlineManifest.clear).mockClear()
  })

  it('sends CACHE_AUDIO message to SW', async () => {
    const song = h.factory('song').make()
    const sourceUrl = 'http://localhost/play/abc123?expires=1&signature=s'
    h.mock(playableStore, 'resolveSourceUrl').mockResolvedValue(sourceUrl)

    await makeAvailableOffline(song)

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'CACHE_AUDIO',
      songId: song.id,
      sourceUrl,
    })
  })

  it('marks a song as failed when its source URL cannot be resolved', async () => {
    const song = h.factory('song').make()
    h.mock(playableStore, 'resolveSourceUrl').mockRejectedValue(new Error('offline'))

    await makeAvailableOffline(song)

    expect(postMessageMock).not.toHaveBeenCalled()
    expect(isCaching(song)).toBe(false)
    expect(hasCachingError(song)).toBe(true)
  })

  it('sends DELETE_AUDIO_CACHE message to SW', () => {
    const song = h.factory('song').make()
    const sourceUrl = 'http://localhost/play/abc123'
    h.mock(playableStore, 'getUnsignedSourceUrl').mockReturnValue(sourceUrl)

    removeOfflineCache(song)

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'DELETE_AUDIO_CACHE',
      songId: song.id,
      sourceUrl,
    })
  })

  it('tracks caching progress from SW messages', () => {
    const song = h.factory('song').make()

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
    const song = h.factory('song').make()
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
    const song = h.factory('song').make()

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
    const song = h.factory('song').make()

    cachingProgress.value.set(song.id, 0.3)

    simulateMessage({
      type: 'CACHE_AUDIO_ERROR',
      songId: song.id,
      error: 'Network error',
    })

    expect(isCaching(song)).toBe(false)
  })

  it('sends GET_CACHE_STATUS message to SW', () => {
    const songs = [h.factory('song').make(), h.factory('song').make()]
    const urls = ['http://localhost/play/a?t=t1', 'http://localhost/play/b?t=t2']
    h.mock(playableStore, 'getUnsignedSourceUrl').mockReturnValueOnce(urls[0]).mockReturnValueOnce(urls[1])

    checkCacheStatus(songs)

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'GET_CACHE_STATUS',
      sourceUrls: urls,
    })
  })

  it('returns caching progress value', () => {
    const song = h.factory('song').make()
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
    const song = h.factory('song').make()
    const sourceUrl = 'http://localhost/play/x?t=token'
    h.mock(playableStore, 'byId').mockReturnValue(song)
    h.mock(playableStore, 'getUnsignedSourceUrl').mockReturnValue(sourceUrl)

    cachedSongIds.value.add(song.id)
    manifestEntries.value = [{ playable: song, cachedAt: Date.now(), size: 0 }]

    await clearAllOfflineCache()

    expect(cachedSongIds.value.size).toBe(0)
    expect(manifestEntries.value).toHaveLength(0)
    expect(offlineManifest.clear).toHaveBeenCalled()
  })

  it('removes offline versions when all playables are cached, in a toast that settles once they are gone', async () => {
    const songs = h.factory('song').make(2)
    songs.forEach(song => cachedSongIds.value.add(song.id))
    h.mock(playableStore, 'getUnsignedSourceUrl').mockReturnValue('http://localhost/play/abc123')

    togglePlayablesOffline(songs, 'favorites')

    songs.forEach(song =>
      expect(postMessageMock).toHaveBeenCalledWith({
        type: 'DELETE_AUDIO_CACHE',
        songId: song.id,
        sourceUrl: 'http://localhost/play/abc123',
      }),
    )

    const [removing] = toastPromiseMock.mock.calls[0]
    songs.forEach(song =>
      simulateMessage({
        type: 'DELETE_AUDIO_CACHE_COMPLETE',
        songId: song.id,
      }),
    )

    await expect(removing).resolves.toBeUndefined()
  })

  it('makes the uncached playables available offline when not all are cached', async () => {
    const [cachedSong, uncachedSong] = h.factory('song').make(2)
    cachedSongIds.value.add(cachedSong.id)
    const sourceUrl = 'http://localhost/play/abc123?expires=1&signature=s'
    h.mock(playableStore, 'resolveSourceUrl').mockResolvedValue(sourceUrl)

    togglePlayablesOffline([cachedSong, uncachedSong], 'favorites')
    await h.tick()

    expect(postMessageMock).toHaveBeenCalledOnce()

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'CACHE_AUDIO',
      songId: uncachedSong.id,
      sourceUrl,
    })
  })

  it('shows the caching progress in a toast that settles with the failure count', async () => {
    const [okSong, failingSong] = h.factory('song').make(2)
    h.mock(playableStore, 'resolveSourceUrl').mockResolvedValue('http://localhost/play/abc123')

    togglePlayablesOffline([okSong, failingSong])
    await h.tick()

    const [caching, messages] = toastPromiseMock.mock.calls[0]

    simulateMessage({
      type: 'CACHE_AUDIO_PROGRESS',
      songId: okSong.id,
      progress: 1,
    })
    expect(messages.loading.progress()).toBe(50)

    simulateMessage({
      type: 'CACHE_AUDIO_COMPLETE',
      songId: okSong.id,
    })
    simulateMessage({
      type: 'CACHE_AUDIO_ERROR',
      songId: failingSong.id,
      error: 'Nope',
    })

    await expect(caching).resolves.toBe(1)
    expect(messages.warning(1)).toBeTruthy()
    expect(messages.warning(0)).toBeUndefined()
  })

  it('does nothing when no playable is a song', () => {
    togglePlayablesOffline(h.factory('episode').make(2), 'favorites')

    expect(postMessageMock).not.toHaveBeenCalled()
    expect(toastPromiseMock).not.toHaveBeenCalled()
  })

  it('tells whether any playable can be made available offline', () => {
    expect(hasOfflineCapablePlayables(h.factory('episode').make(2))).toBe(false)
    expect(hasOfflineCapablePlayables([h.factory('episode').make(), h.factory('song').make()])).toBe(true)
  })
})
