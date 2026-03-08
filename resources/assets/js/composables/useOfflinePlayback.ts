import { computed, ref } from 'vue'
import { playableStore } from '@/stores/playableStore'
import { offlineManifest } from '@/services/offlineManifest'
import type { OfflineManifestEntry } from '@/services/offlineManifest'
import { logger } from '@/utils/logger'
import { isSong } from '@/utils/typeGuards'

type CacheProgress = {
  songId: string
  progress: number
  received: number
  total: number
}

/**
 * Tracks which songs are currently cached for offline playback.
 * Key: song ID, Value: true if cached.
 */
const cachedSongIds = ref(new Set<string>())

/**
 * Full manifest entries loaded from IndexedDB.
 */
const manifestEntries = ref<OfflineManifestEntry[]>([])

/**
 * Tracks songs currently being cached.
 * Key: song ID, Value: download progress (0-1).
 */
const cachingProgress = ref(new Map<string, number>())

/** Storage estimate from navigator.storage API. */
const storageUsage = ref(0)
const storageQuota = ref(0)

/** Whether the manifest has been loaded from IndexedDB. */
let manifestLoaded = false

/**
 * Reactive flag that becomes true once a service worker is active and ready.
 * Uses navigator.serviceWorker.ready (resolves when an active SW exists)
 * and listens for 'controllerchange' to cover the initial registration case
 * without requiring a page reload.
 */
const swReady = ref(Boolean(navigator.serviceWorker?.controller))

if (navigator.serviceWorker) {
  navigator.serviceWorker.ready.then(() => {
    swReady.value = true
  })

  navigator.serviceWorker.addEventListener('controllerchange', () => {
    swReady.value = true
  })
}

const getSW = (): ServiceWorker | null => navigator.serviceWorker?.controller || null

const loadManifest = async () => {
  if (manifestLoaded) return
  manifestLoaded = true

  const entries = await offlineManifest.getAll()
  manifestEntries.value = entries

  for (const entry of entries) {
    cachedSongIds.value.add(entry.songId)
  }

  await refreshStorageEstimate()
}

const refreshStorageEstimate = async () => {
  if (!navigator.storage?.estimate) return

  const estimate = await navigator.storage.estimate()
  storageUsage.value = estimate.usage || 0
  storageQuota.value = estimate.quota || 0
}

const setupMessageListener = () => {
  navigator.serviceWorker?.addEventListener('message', event => {
    const { data } = event

    switch (data.type) {
      case 'CACHE_AUDIO_PROGRESS': {
        const { songId, progress } = data as CacheProgress & { type: string }
        cachingProgress.value.set(songId, progress)
        break
      }

      case 'CACHE_AUDIO_COMPLETE': {
        const { songId } = data
        cachedSongIds.value.add(songId)
        cachingProgress.value.delete(songId)
        persistManifestEntry(songId)
        refreshStorageEstimate()
        break
      }

      case 'CACHE_AUDIO_ERROR': {
        const { songId, error } = data
        cachingProgress.value.delete(songId)
        logger.error(`Failed to cache song ${songId}: ${error}`)
        break
      }

      case 'DELETE_AUDIO_CACHE_COMPLETE': {
        const { songId } = data
        cachedSongIds.value.delete(songId)
        manifestEntries.value = manifestEntries.value.filter(e => e.songId !== songId)
        offlineManifest.remove(songId)
        refreshStorageEstimate()
        break
      }

      case 'CACHE_STATUS': {
        const { statuses } = data as { type: string; statuses: Record<string, boolean> }

        for (const [url, isCachedUrl] of Object.entries(statuses)) {
          if (isCachedUrl) {
            const songId = extractSongIdFromUrl(url)
            if (songId) {
              cachedSongIds.value.add(songId)
            }
          }
        }
        break
      }
    }
  })
}

const persistManifestEntry = (songId: string) => {
  const playable = playableStore.byId(songId)

  if (!playable) return

  const entry: OfflineManifestEntry = {
    songId,
    title: playable.title,
    artist: isSong(playable) ? playable.artist_name : (playable as Episode).podcast_author,
    album: isSong(playable) ? playable.album_name : (playable as Episode).podcast_title,
    cachedAt: Date.now(),
    size: 0,
  }

  offlineManifest.put(entry)
  manifestEntries.value = [...manifestEntries.value.filter(e => e.songId !== songId), entry]
}

let listenerSetup = false

const extractSongIdFromUrl = (url: string): string | null => {
  const match = url.match(/\/play\/([^/?]+)/)
  return match?.[1] || null
}

export const useOfflinePlayback = () => {
  if (!listenerSetup) {
    listenerSetup = true
    setupMessageListener()
    loadManifest()
  }

  const makeAvailableOffline = (playable: Playable) => {
    const sw = getSW()
    if (!sw) return

    const sourceUrl = playableStore.getSourceUrl(playable)

    cachingProgress.value.set(playable.id, 0)
    sw.postMessage({
      type: 'CACHE_AUDIO',
      songId: playable.id,
      sourceUrl,
    })
  }

  const removeOfflineCache = (playable: Playable) => {
    const sw = getSW()
    if (!sw) return

    const sourceUrl = playableStore.getSourceUrl(playable)

    sw.postMessage({
      type: 'DELETE_AUDIO_CACHE',
      songId: playable.id,
      sourceUrl,
    })
  }

  const clearAllOfflineCache = async () => {
    const sw = getSW()
    if (!sw) return

    const entries = [...manifestEntries.value]

    for (const entry of entries) {
      const playable = playableStore.byId(entry.songId)

      if (playable) {
        const sourceUrl = playableStore.getSourceUrl(playable)
        sw.postMessage({
          type: 'DELETE_AUDIO_CACHE',
          songId: entry.songId,
          sourceUrl,
        })
      } else {
        // Song no longer in vault — just clean up manifest
        cachedSongIds.value.delete(entry.songId)
        offlineManifest.remove(entry.songId)
      }
    }

    manifestEntries.value = []
    cachedSongIds.value.clear()
    await offlineManifest.clear()
    await refreshStorageEstimate()
  }

  const isCached = (playable: Playable): boolean => cachedSongIds.value.has(playable.id)
  const isCaching = (playable: Playable): boolean => cachingProgress.value.has(playable.id)
  const getCachingProgress = (playable: Playable): number => cachingProgress.value.get(playable.id) ?? 0

  const cachedSongCount = computed(() => cachedSongIds.value.size)

  const checkCacheStatus = (playables: Playable[]) => {
    const sw = getSW()
    if (!sw) return

    const sourceUrls = playables.map(p => playableStore.getSourceUrl(p))

    sw.postMessage({
      type: 'GET_CACHE_STATUS',
      sourceUrls,
    })
  }

  const makePlayablesAvailableOffline = (playables: Playable[]) => {
    playables.filter(p => isSong(p) && !isCached(p)).forEach(p => makeAvailableOffline(p))
  }

  const removePlayablesOfflineCache = (playables: Playable[]) => {
    playables.filter(p => isSong(p) && isCached(p)).forEach(p => removeOfflineCache(p))
  }

  const allPlayablesCached = (playables: Playable[]): boolean => {
    const songs = playables.filter(p => isSong(p))
    return songs.length > 0 && songs.every(p => isCached(p))
  }

  return {
    swReady,
    cachedSongIds,
    cachingProgress,
    manifestEntries,
    storageUsage,
    storageQuota,
    cachedSongCount,
    makeAvailableOffline,
    removeOfflineCache,
    clearAllOfflineCache,
    makePlayablesAvailableOffline,
    removePlayablesOfflineCache,
    allPlayablesCached,
    isCached,
    isCaching,
    getCachingProgress,
    checkCacheStatus,
    refreshStorageEstimate,
  }
}
