import { computed, ref, toRaw } from 'vue'
import { playableStore } from '@/stores/playableStore'
import { offlineManifest } from '@/services/offlineManifest'
import type { OfflineManifestEntry } from '@/services/offlineManifest'
import { http } from '@/services/http'
import { eventBus } from '@/utils/eventBus'
import { logger } from '@/utils/logger'
import { isSong } from '@/utils/typeGuards'
import { addCurrentAudioToken, normalizeAudioCacheKey } from '@/utils/audioCache'
import type { AudioCacheCompletionMessage } from '@/utils/audioCache'

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

export const isPlayableCachedForOffline = (playable: Playable): boolean => cachedSongIds.value.has(playable.id)

/**
 * Full manifest entries loaded from IndexedDB.
 */
const manifestEntries = ref<OfflineManifestEntry[]>([])

const findManifestEntry = (playable: Playable): OfflineManifestEntry | undefined =>
  manifestEntries.value.find(entry => entry.playable.id === playable.id)

const getManifestEntrySourceUrl = (entry: OfflineManifestEntry): string =>
  entry.sourceUrl
    ? addCurrentAudioToken(entry.sourceUrl, playableStore.getSourceUrl(entry.playable))
    : playableStore.getSourceUrl(entry.playable)

const getStoredOfflineSourceUrl = (playable: Playable): string | null => {
  const entry = findManifestEntry(playable)

  return entry ? getManifestEntrySourceUrl(entry) : null
}

export const getCachedOfflineSourceUrl = (playable: Playable): string | null => {
  if (!isPlayableCachedForOffline(playable)) {
    return null
  }

  return getStoredOfflineSourceUrl(playable) ?? playableStore.getSourceUrl(playable)
}

/**
 * Tracks songs currently being cached.
 * Key: song ID, Value: download progress (0-1).
 */
const cachingProgress = ref(new Map<Song['id'], number>())
const cachingSourceUrls = new Map<Song['id'], string>()

/**
 * Tracks songs whose caching failed.
 * Key: song ID, Value: error message.
 */
const cachingErrors = ref(new Map<Song['id'], string>())

/** Storage estimate from navigator.storage API. */
const storageUsage = ref(0)
const storageQuota = ref(0)

let manifestLoadPromise: Promise<void> | null = null
let listenerSetup = false
let audioCacheRecoveryRequested = false

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
    audioCacheRecoveryRequested = false
    requestAudioCacheCompletionRecovery()
  })
}

const getSW = (): ServiceWorker | null => navigator.serviceWorker?.controller || null

const getOfflineSourceUrl = (playable: Playable): string => playableStore.getSourceUrl(playable)

const loadManifest = async () => {
  const loadedEntries = await offlineManifest.getAll()
  const entriesByPlayableId = new Map<Song['id'], OfflineManifestEntry>(
    loadedEntries.map(entry => [entry.playable.id, entry]),
  )

  for (const entry of manifestEntries.value) {
    const loadedEntry = entriesByPlayableId.get(entry.playable.id)

    if (!loadedEntry || entry.cachedAt >= loadedEntry.cachedAt) {
      entriesByPlayableId.set(entry.playable.id, entry)
    }
  }

  const entries = [...entriesByPlayableId.values()]
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

const requestAudioCacheCompletionRecovery = () => {
  const sw = getSW()

  if (!listenerSetup || !sw || audioCacheRecoveryRequested) {
    return
  }

  audioCacheRecoveryRequested = true
  sw.postMessage({ type: 'RECOVER_AUDIO_CACHE_COMPLETIONS' })
}

export const initializeOfflinePlayback = async () => {
  setupOfflinePlaybackListeners()
  requestAudioCacheCompletionRecovery()
  manifestLoadPromise ??= loadManifest()

  try {
    await manifestLoadPromise
  } catch (error: unknown) {
    manifestLoadPromise = null
    logger.warn('Failed to load the offline playback manifest:', error)
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
    const syncedIds = new Set(cachedIds)
    const entriesById = new Map(entries.map(entry => [entry.playable.id, entry]))
    const updatedEntriesById = new Map<Song['id'], OfflineManifestEntry>()

    // Update existing entries with fresh data
    for (const playable of freshPlayables) {
      const existingEntry = entriesById.get(playable.id)
      const updatedEntry: OfflineManifestEntry = {
        playable,
        cachedAt: Date.now(),
        size: existingEntry?.size ?? 0,
        sourceUrl: existingEntry?.sourceUrl,
      }

      playableStore.syncWithVault(playable)
      offlineManifest.put(updatedEntry)
      updatedEntriesById.set(playable.id, updatedEntry)
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
            sourceUrl: getManifestEntrySourceUrl(entry),
          })
        }
      }
    }

    manifestEntries.value = manifestEntries.value
      .filter(entry => !syncedIds.has(entry.playable.id) || freshIds.has(entry.playable.id))
      .map(entry => updatedEntriesById.get(entry.playable.id) ?? entry)
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
        handleAudioCacheCompletion(data as AudioCacheCompletionMessage)
        break
      }

      case 'AUDIO_CACHE_COMPLETIONS_RECOVERED': {
        const { completions } = data as { completions: AudioCacheCompletionMessage[] }
        completions.forEach(completion => handleAudioCacheCompletion(completion))
        break
      }

      case 'CACHE_AUDIO_ERROR': {
        const { songId, error } = data
        cachingProgress.value.delete(songId)
        cachingSourceUrls.delete(songId)
        cachingErrors.value.set(songId, error || 'Unknown error')
        logger.error(`Failed to cache song ${songId}: ${error}`)
        break
      }

      case 'DELETE_AUDIO_CACHE_COMPLETE': {
        const { songId, sourceUrl } = data as { songId: Song['id']; sourceUrl?: string }
        const manifestEntry = manifestEntries.value.find(entry => entry.playable.id === songId)

        if (
          sourceUrl &&
          manifestEntry?.sourceUrl &&
          normalizeAudioCacheKey(sourceUrl) !== normalizeAudioCacheKey(manifestEntry.sourceUrl)
        ) {
          break
        }

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
              persistManifestEntry(songId, url)
            }
          }
        }
        break
      }
    }
  })
}

const handleAudioCacheCompletion = (completion: AudioCacheCompletionMessage) => {
  const { songId, playable } = completion
  const sourceUrl = completion.sourceUrl || cachingSourceUrls.get(songId)
  cachedSongIds.value.add(songId)
  cachingProgress.value.delete(songId)
  cachingSourceUrls.delete(songId)

  const persistence = persistManifestEntry(songId, sourceUrl, playable?.id === songId ? playable : undefined)

  if (persistence && sourceUrl) {
    void persistence.then(persisted => {
      if (persisted) {
        getSW()?.postMessage({
          type: 'ACK_AUDIO_CACHE_COMPLETION',
          songId,
          sourceUrl: normalizeAudioCacheKey(sourceUrl),
        })
      }
    })
  }

  void refreshStorageEstimate()
}

const persistManifestEntry = (songId: Song['id'], sourceUrl?: string, completedPlayable?: Playable) => {
  const playable = playableStore.byId(songId) ?? completedPlayable

  if (!playable) return null

  const entry: OfflineManifestEntry = {
    playable: toRaw(playable),
    cachedAt: Date.now(),
    size: 0,
    sourceUrl: sourceUrl ? normalizeAudioCacheKey(sourceUrl) : undefined,
  }

  manifestEntries.value = [...manifestEntries.value.filter(e => e.playable.id !== songId), entry]

  return offlineManifest.put(entry)
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
          sourceUrl: getStoredOfflineSourceUrl(song) ?? playableStore.getSourceUrl(song),
        })
      }
    }

    manifestEntries.value = manifestEntries.value.filter(e => !deletedSongs.some(s => s.id === e.playable.id))
  })
}

const extractSongIdFromUrl = (url: string): Song['id'] | null => {
  const match = url.match(/\/play\/([^/?]+)/)
  return match?.[1] || null
}

export const shouldWarnUponWindowUnload = () => cachingProgress.value.size > 0

const setupOfflinePlaybackListeners = () => {
  if (listenerSetup) {
    return
  }

  listenerSetup = true
  setupMessageListener()
  setupSongDeletionListener()
}

export const useOfflinePlayback = () => {
  void initializeOfflinePlayback()

  const makeAvailableOffline = (playable: Playable) => {
    const sw = getSW()
    if (!sw) return

    const sourceUrl = getOfflineSourceUrl(playable)

    cachingProgress.value.set(playable.id, 0)
    cachingSourceUrls.set(playable.id, sourceUrl)
    cachingErrors.value.delete(playable.id)
    sw.postMessage({
      type: 'CACHE_AUDIO',
      songId: playable.id,
      sourceUrl,
      playable: toRaw(playable),
    })
  }

  const removeOfflineCache = (playable: Playable) => {
    const sw = getSW()
    if (!sw) return

    const sourceUrl = getStoredOfflineSourceUrl(playable) ?? playableStore.getSourceUrl(playable)

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
      const sourceUrl = getManifestEntrySourceUrl(entry)
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

  const isCached = isPlayableCachedForOffline
  const isCaching = (playable: Playable): boolean => cachingProgress.value.has(playable.id)
  const getCachingProgress = (playable: Playable): number => cachingProgress.value.get(playable.id) ?? 0
  const hasCachingError = (playable: Playable): boolean => cachingErrors.value.has(playable.id)
  const getCachingError = (playable: Playable): string | undefined => cachingErrors.value.get(playable.id)

  const cachedSongCount = computed(() => cachedSongIds.value.size)

  const checkCacheStatus = (playables: Playable[]) => {
    const sw = getSW()
    if (!sw) return

    const sourceUrls = playables.map(playable => {
      const entry = findManifestEntry(playable)

      return entry ? getManifestEntrySourceUrl(entry) : getOfflineSourceUrl(playable)
    })

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
