import { computed, ref, toRaw } from 'vue'
import { playableStore } from '@/stores/playableStore'
import { offlineManifest } from '@/services/offlineManifest'
import type { OfflineManifestEntry } from '@/services/offlineManifest'
import { http } from '@/services/http'
import { eventBus } from '@/utils/eventBus'
import { logger } from '@/utils/logger'
import { isSong } from '@/utils/typeGuards'

type CacheProgress = {
  songId: Song['id']
  progress: number
  received: number
  total: number
}

/**
 * Tracks which songs are currently cached for offline playback.
 * Key: song ID, Value: true if cached.
 */
const cachedSongIds = ref(new Set<Song['id']>())

/**
 * Full manifest entries loaded from IndexedDB.
 */
const manifestEntries = ref<OfflineManifestEntry[]>([])

/**
 * Tracks songs currently being cached.
 * Key: song ID, Value: download progress (0-1).
 */
const cachingProgress = ref(new Map<Song['id'], number>())

/**
 * Tracks songs whose caching failed.
 * Key: song ID, Value: error message.
 */
const cachingErrors = ref(new Map<Song['id'], string>())

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
    cachedSongIds.value.add(entry.playable.id)

    // Restore the playable into the vault if it's not already there
    if (!playableStore.byId(entry.playable.id)) {
      playableStore.syncWithVault(entry.playable)
    }
  }

  await refreshStorageEstimate()

  // Sync with the server after a delay to avoid competing with init requests
  if (entries.length) {
    setTimeout(() => syncWithServer(entries), 3 * 60 * 1000) // 3 minutes
  }
}

/**
 * Sync offline manifest with the server:
 * - Fetch fresh data for all cached song IDs
 * - Update manifest entries with current metadata (e.g. changed lyrics, title)
 * - Remove entries for songs that no longer exist on the server
 * - Clean up the corresponding audio cache for removed songs
 */
const syncWithServer = async (entries: OfflineManifestEntry[]) => {
  try {
    const cachedIds = entries.map(e => e.playable.id)
    const freshPlayables = await http.silently.post<Playable[]>('songs/by-ids', { ids: cachedIds })
    const freshIds = new Set(freshPlayables.map(p => p.id))

    // Update existing entries with fresh data
    for (const playable of freshPlayables) {
      playableStore.syncWithVault(playable)
      offlineManifest.put({ playable, cachedAt: Date.now(), size: 0 })
    }

    // Remove orphans (songs deleted from server)
    const sw = getSW()

    for (const entry of entries) {
      if (!freshIds.has(entry.playable.id)) {
        cachedSongIds.value.delete(entry.playable.id)
        offlineManifest.remove(entry.playable.id)

        if (sw) {
          sw.postMessage({
            type: 'DELETE_AUDIO_CACHE',
            songId: entry.playable.id,
            sourceUrl: playableStore.getSourceUrl(entry.playable),
          })
        }
      }
    }

    manifestEntries.value = manifestEntries.value.filter(e => freshIds.has(e.playable.id))
  } catch (e) {
    logger.warn('Failed to sync offline cache with server:', e)
  }
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
        const { songId, progress } = data as CacheProgress & { type: Song['id'] }
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
        cachingErrors.value.set(songId, error || 'Unknown error')
        logger.error(`Failed to cache song ${songId}: ${error}`)
        break
      }

      case 'DELETE_AUDIO_CACHE_COMPLETE': {
        const { songId } = data
        cachedSongIds.value.delete(songId)
        manifestEntries.value = manifestEntries.value.filter(e => e.playable.id !== songId)
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

const persistManifestEntry = (songId: Song['id']) => {
  const playable = playableStore.byId(songId)

  if (!playable) return

  const entry: OfflineManifestEntry = {
    playable: toRaw(playable),
    cachedAt: Date.now(),
    size: 0,
  }

  offlineManifest.put(entry)
  manifestEntries.value = [...manifestEntries.value.filter(e => e.playable.id !== songId), entry]
}

const setupSongDeletionListener = () => {
  eventBus.on('SONGS_DELETED', deletedSongs => {
    const sw = getSW()

    for (const song of deletedSongs) {
      if (!cachedSongIds.value.has(song.id)) {
        continue
      }

      cachedSongIds.value.delete(song.id)
      offlineManifest.remove(song.id)

      if (sw) {
        sw.postMessage({
          type: 'DELETE_AUDIO_CACHE',
          songId: song.id,
          sourceUrl: playableStore.getSourceUrl(song),
        })
      }
    }

    manifestEntries.value = manifestEntries.value.filter(e => !deletedSongs.some(s => s.id === e.playable.id))
  })
}

let listenerSetup = false

const extractSongIdFromUrl = (url: string): Song['id'] | null => {
  const match = url.match(/\/play\/([^/?]+)/)
  return match?.[1] || null
}

export const shouldWarnUponWindowUnload = () => cachingProgress.value.size > 0

export const useOfflinePlayback = () => {
  if (!listenerSetup) {
    listenerSetup = true
    setupMessageListener()
    loadManifest()
    setupSongDeletionListener()
  }

  const makeAvailableOffline = (playable: Playable) => {
    const sw = getSW()
    if (!sw) return

    const sourceUrl = playableStore.getSourceUrl(playable)

    cachingProgress.value.set(playable.id, 0)
    cachingErrors.value.delete(playable.id)
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
      const sourceUrl = playableStore.getSourceUrl(entry.playable)
      sw.postMessage({
        type: 'DELETE_AUDIO_CACHE',
        songId: entry.playable.id,
        sourceUrl,
      })
    }

    manifestEntries.value = []
    cachedSongIds.value.clear()
    await offlineManifest.clear()
    await refreshStorageEstimate()
  }

  const isCached = (playable: Playable): boolean => cachedSongIds.value.has(playable.id)
  const isCaching = (playable: Playable): boolean => cachingProgress.value.has(playable.id)
  const getCachingProgress = (playable: Playable): number => cachingProgress.value.get(playable.id) ?? 0
  const hasCachingError = (playable: Playable): boolean => cachingErrors.value.has(playable.id)
  const getCachingError = (playable: Playable): string | undefined => cachingErrors.value.get(playable.id)

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
    hasCachingError,
    getCachingError,
    shouldWarnUponWindowUnload,
    checkCacheStatus,
    refreshStorageEstimate,
  }
}
