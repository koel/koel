import { beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playableStore } from '@/stores/playableStore'
import { offlineManifest } from '@/services/offlineManifest'
import type { OfflineManifestEntry } from '@/services/offlineManifest'
import { eventBus } from '@/utils/eventBus'
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

  it('deletes a removed song using its persisted codec-aware cache key', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const canonicalUrl = `http://localhost/play/${song.id}?t=current-token`
    const cachedSourceUrl = `http://localhost/play/${song.id}?codec=aac`
    h.mock(playableStore, 'getSourceUrl').mockReturnValue(canonicalUrl)
    cachedSongIds.value.add(song.id)
    manifestEntries.value = [{ playable: song, cachedAt: Date.now(), size: 0, sourceUrl: cachedSourceUrl }]

    eventBus.emit('SONGS_DELETED', [song])

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'DELETE_AUDIO_CACHE',
      songId: song.id,
      sourceUrl: `${cachedSourceUrl}&t=current-token`,
    })
  })

  it('sends CACHE_AUDIO message to SW', () => {
    const song = h.factory('song').make()
    const sourceUrl = 'http://localhost/play/abc123?t=token'
    h.mock(playableStore, 'getSourceUrl').mockReturnValue(sourceUrl)

    makeAvailableOffline(song)

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'CACHE_AUDIO',
      songId: song.id,
      sourceUrl,
      playable: expect.objectContaining({ id: song.id }),
    })
  })

  it('caches the plain source URL for a song requiring transcoding, never a progressive URL', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const sourceUrl = `http://localhost/play/${song.id}?t=token`
    h.mock(playableStore, 'getSourceUrl').mockReturnValue(sourceUrl)

    makeAvailableOffline(song)

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'CACHE_AUDIO',
      songId: song.id,
      sourceUrl,
      playable: expect.objectContaining({ id: song.id }),
    })
    expect(postMessageMock).not.toHaveBeenCalledWith(
      expect.objectContaining({ sourceUrl: expect.stringContaining('progressive=1') }),
    )
  })

  it('sends DELETE_AUDIO_CACHE message to SW', () => {
    const song = h.factory('song').make()
    const sourceUrl = 'http://localhost/play/abc123?t=token'
    h.mock(playableStore, 'getSourceUrl').mockReturnValue(sourceUrl)

    removeOfflineCache(song)

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'DELETE_AUDIO_CACHE',
      songId: song.id,
      sourceUrl,
    })
  })

  it('deletes the persisted codec-aware cache key', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const canonicalUrl = `http://localhost/play/${song.id}?t=current-token`
    const cachedSourceUrl = `http://localhost/play/${song.id}?codec=aac`
    h.mock(playableStore, 'getSourceUrl').mockReturnValue(canonicalUrl)
    cachedSongIds.value.add(song.id)
    manifestEntries.value = [{ playable: song, cachedAt: Date.now(), size: 0, sourceUrl: cachedSourceUrl }]

    removeOfflineCache(song)

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'DELETE_AUDIO_CACHE',
      songId: song.id,
      sourceUrl: `${cachedSourceUrl}&t=current-token`,
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

  it('persists the token-free source URL after caching completes', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const sourceUrl = `http://localhost/play/${song.id}?t=token`
    h.mock(playableStore, 'getSourceUrl').mockReturnValue(sourceUrl)
    h.mock(playableStore, 'byId').mockReturnValue(song)

    makeAvailableOffline(song)
    simulateMessage({ type: 'CACHE_AUDIO_COMPLETE', songId: song.id })

    expect(offlineManifest.put).toHaveBeenCalledWith(
      expect.objectContaining({
        sourceUrl: sourceUrl.replace('?t=token', ''),
      }),
    )
  })

  it('persists the normalized cache key echoed by the service worker', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const normalizedSourceUrl = `http://localhost/play/${song.id}?codec=aac`
    h.mock(playableStore, 'byId').mockReturnValue(song)

    simulateMessage({
      type: 'CACHE_AUDIO_COMPLETE',
      songId: song.id,
      sourceUrl: normalizedSourceUrl,
    })

    expect(offlineManifest.put).toHaveBeenCalledWith(
      expect.objectContaining({
        sourceUrl: normalizedSourceUrl,
      }),
    )
  })

  it('persists codec-aware completion metadata when the playable vault is empty after reload', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const normalizedSourceUrl = `http://localhost/play/${song.id}?codec=opus`
    h.mock(playableStore, 'byId').mockReturnValue(undefined)

    simulateMessage({
      type: 'CACHE_AUDIO_COMPLETE',
      songId: song.id,
      sourceUrl: normalizedSourceUrl,
      playable: song,
    })

    expect(offlineManifest.put).toHaveBeenCalledWith(
      expect.objectContaining({
        playable: expect.objectContaining({ id: song.id }),
        sourceUrl: normalizedSourceUrl,
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

  it('does not let a stale codec deletion remove a newer cached completion', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const opusSourceUrl = `http://localhost/play/${song.id}?codec=opus`
    cachedSongIds.value.add(song.id)
    manifestEntries.value = [{ playable: song, cachedAt: Date.now(), size: 0, sourceUrl: opusSourceUrl }]

    simulateMessage({
      type: 'DELETE_AUDIO_CACHE_COMPLETE',
      songId: song.id,
      sourceUrl: `http://localhost/play/${song.id}?codec=aac`,
    })

    expect(isCached(song)).toBe(true)
    expect(manifestEntries.value).toEqual([expect.objectContaining({ sourceUrl: opusSourceUrl })])
    expect(offlineManifest.remove).not.toHaveBeenCalledWith(song.id)
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
    h.mock(playableStore, 'getSourceUrl').mockReturnValueOnce(urls[0]).mockReturnValueOnce(urls[1])

    checkCacheStatus(songs)

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'GET_CACHE_STATUS',
      sourceUrls: urls,
    })
  })

  it('checks cache status with persisted codec-aware keys', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const canonicalUrl = `http://localhost/play/${song.id}?t=current-token`
    const cachedSourceUrl = `http://localhost/play/${song.id}?codec=aac`
    h.mock(playableStore, 'getSourceUrl').mockReturnValue(canonicalUrl)
    cachedSongIds.value.add(song.id)
    manifestEntries.value = [{ playable: song, cachedAt: Date.now(), size: 0, sourceUrl: cachedSourceUrl }]

    checkCacheStatus([song])

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'GET_CACHE_STATUS',
      sourceUrls: [`${cachedSourceUrl}&t=current-token`],
    })
  })

  it('restores codec-aware manifest metadata from cache status', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const sourceUrl = `http://localhost/play/${song.id}?codec=aac&t=current-token`
    const normalizedSourceUrl = `http://localhost/play/${song.id}?codec=aac`
    h.mock(playableStore, 'byId').mockReturnValue(song)

    simulateMessage({ type: 'CACHE_STATUS', statuses: { [sourceUrl]: true } })

    expect(cachedSongIds.value.has(song.id)).toBe(true)
    expect(offlineManifest.put).toHaveBeenCalledWith(
      expect.objectContaining({
        sourceUrl: normalizedSourceUrl,
      }),
    )
    expect(manifestEntries.value).toEqual([
      expect.objectContaining({
        sourceUrl: normalizedSourceUrl,
      }),
    ])
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
    h.mock(playableStore, 'getSourceUrl').mockReturnValue(sourceUrl)

    cachedSongIds.value.add(song.id)
    manifestEntries.value = [{ playable: song, cachedAt: Date.now(), size: 0 }]

    await clearAllOfflineCache()

    expect(cachedSongIds.value.size).toBe(0)
    expect(manifestEntries.value).toHaveLength(0)
    expect(offlineManifest.clear).toHaveBeenCalled()
  })

  it('clears codec-aware cache entries using their persisted keys', async () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const canonicalUrl = `http://localhost/play/${song.id}?t=current-token`
    const cachedSourceUrl = `http://localhost/play/${song.id}?codec=opus`
    h.mock(playableStore, 'getSourceUrl').mockReturnValue(canonicalUrl)
    cachedSongIds.value.add(song.id)
    manifestEntries.value = [{ playable: song, cachedAt: Date.now(), size: 0, sourceUrl: cachedSourceUrl }]

    await clearAllOfflineCache()

    expect(postMessageMock).toHaveBeenCalledWith({
      type: 'DELETE_AUDIO_CACHE',
      songId: song.id,
      sourceUrl: `${cachedSourceUrl}&t=current-token`,
    })
  })

  it('installs the service worker message listener during direct initialization', async () => {
    const originalServiceWorker = navigator.serviceWorker
    const addEventListenerMock = vi.fn()

    Object.defineProperty(navigator, 'serviceWorker', {
      configurable: true,
      value: {
        controller: null,
        ready: Promise.resolve(),
        addEventListener: addEventListenerMock,
      },
    })
    vi.resetModules()

    try {
      const { initializeOfflinePlayback } = await import('./useOfflinePlayback')

      await initializeOfflinePlayback()

      expect(addEventListenerMock).toHaveBeenCalledWith('message', expect.any(Function))
    } finally {
      Object.defineProperty(navigator, 'serviceWorker', {
        configurable: true,
        value: originalServiceWorker,
      })
    }
  })

  it('waits until the message listener is installed before requesting recovery after controller change', async () => {
    const originalServiceWorker = navigator.serviceWorker
    const controllerPostMessage = vi.fn()
    let controllerChangeListener: (() => void) | undefined

    Object.defineProperty(navigator, 'serviceWorker', {
      configurable: true,
      value: {
        controller: { postMessage: controllerPostMessage },
        ready: Promise.resolve(),
        addEventListener: (eventName: string, listener: () => void) => {
          if (eventName === 'controllerchange') {
            controllerChangeListener = listener
          }
        },
      },
    })
    vi.resetModules()

    try {
      const { initializeOfflinePlayback } = await import('./useOfflinePlayback')

      controllerChangeListener?.()
      expect(controllerPostMessage).not.toHaveBeenCalled()

      await initializeOfflinePlayback()

      expect(controllerPostMessage).toHaveBeenCalledWith({ type: 'RECOVER_AUDIO_CACHE_COMPLETIONS' })
    } finally {
      Object.defineProperty(navigator, 'serviceWorker', {
        configurable: true,
        value: originalServiceWorker,
      })
    }
  })

  it('recovers a completed cache from before initialization with its playable and exact codec key', async () => {
    const originalServiceWorker = navigator.serviceWorker
    const song = h.factory('song').make({ requires_transcoding: true })
    const completedSourceUrl = `http://localhost/play/${song.id}?codec=opus`
    let messageListener: ((event: MessageEvent) => void) | undefined
    const recoveryPostMessage = vi.fn((message: { type: string }) => {
      if (message.type === 'RECOVER_AUDIO_CACHE_COMPLETIONS') {
        messageListener?.(
          new MessageEvent('message', {
            data: {
              type: 'AUDIO_CACHE_COMPLETIONS_RECOVERED',
              completions: [
                {
                  type: 'CACHE_AUDIO_COMPLETE',
                  songId: song.id,
                  sourceUrl: completedSourceUrl,
                  playable: song,
                },
              ],
            },
          }),
        )
      }
    })

    Object.defineProperty(navigator, 'serviceWorker', {
      configurable: true,
      value: {
        controller: { postMessage: recoveryPostMessage },
        ready: Promise.resolve(),
        addEventListener: (eventName: string, listener: (event: MessageEvent) => void) => {
          if (eventName === 'message') {
            messageListener = listener
          }
        },
      },
    })
    vi.resetModules()

    try {
      const { playableStore: freshPlayableStore } = await import('@/stores/playableStore')
      vi.spyOn(freshPlayableStore, 'byId').mockReturnValue(undefined)
      const { offlineManifest: freshOfflineManifest } = await import('@/services/offlineManifest')
      vi.mocked(freshOfflineManifest.put).mockResolvedValueOnce(true)
      const { initializeOfflinePlayback, useOfflinePlayback: useFreshOfflinePlayback } =
        await import('./useOfflinePlayback')

      await initializeOfflinePlayback()

      await vi.waitFor(() => {
        expect(freshOfflineManifest.put).toHaveBeenCalledWith(
          expect.objectContaining({
            playable: expect.objectContaining({ id: song.id }),
            sourceUrl: completedSourceUrl,
          }),
        )
        expect(recoveryPostMessage).toHaveBeenCalledWith({
          type: 'ACK_AUDIO_CACHE_COMPLETION',
          songId: song.id,
          sourceUrl: completedSourceUrl,
        })
      })
      expect(useFreshOfflinePlayback().manifestEntries.value).toEqual([
        expect.objectContaining({
          playable: expect.objectContaining({ id: song.id }),
          sourceUrl: completedSourceUrl,
        }),
      ])
    } finally {
      Object.defineProperty(navigator, 'serviceWorker', {
        configurable: true,
        value: originalServiceWorker,
      })
    }
  })

  it('keeps codec-aware completion received while a stale manifest snapshot is loading', async () => {
    const originalServiceWorker = navigator.serviceWorker
    const song = h.factory('song').make({ requires_transcoding: true })
    const staleEntry: OfflineManifestEntry & { id: Song['id'] } = {
      id: song.id,
      playable: song,
      cachedAt: 1,
      size: 0,
      sourceUrl: `http://localhost/play/${song.id}?codec=aac`,
    }
    const completedSourceUrl = `http://localhost/play/${song.id}?codec=opus`
    let resolveManifest!: (entries: Array<OfflineManifestEntry & { id: Song['id'] }>) => void
    const manifestSnapshot = new Promise<Array<OfflineManifestEntry & { id: Song['id'] }>>(resolve => {
      resolveManifest = resolve
    })
    let messageListener: ((event: MessageEvent) => void) | undefined

    Object.defineProperty(navigator, 'serviceWorker', {
      configurable: true,
      value: {
        controller: null,
        ready: Promise.resolve(),
        addEventListener: (eventName: string, listener: (event: MessageEvent) => void) => {
          if (eventName === 'message') {
            messageListener = listener
          }
        },
      },
    })
    vi.resetModules()

    try {
      const { offlineManifest: freshOfflineManifest } = await import('@/services/offlineManifest')
      vi.mocked(freshOfflineManifest.getAll).mockReturnValueOnce(manifestSnapshot)
      const { initializeOfflinePlayback, useOfflinePlayback: useFreshOfflinePlayback } =
        await import('./useOfflinePlayback')

      const initialization = initializeOfflinePlayback()
      messageListener?.(
        new MessageEvent('message', {
          data: {
            type: 'CACHE_AUDIO_COMPLETE',
            songId: song.id,
            sourceUrl: completedSourceUrl,
            playable: song,
          },
        }),
      )
      resolveManifest([staleEntry])
      await initialization

      expect(useFreshOfflinePlayback().manifestEntries.value).toEqual([
        expect.objectContaining({
          playable: expect.objectContaining({ id: song.id }),
          sourceUrl: completedSourceUrl,
        }),
      ])
    } finally {
      Object.defineProperty(navigator, 'serviceWorker', {
        configurable: true,
        value: originalServiceWorker,
      })
    }
  })
})
