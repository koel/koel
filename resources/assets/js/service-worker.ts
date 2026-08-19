/// <reference lib="webworker" />

import { isCacheablePlayUrl } from '@/utils/isCacheablePlayUrl'
import {
  createAudioCacheCompletionMessage,
  handleCachedAudioRangeRequest,
  isMatchingAudioCacheCompletion,
  normalizeAudioCacheKey,
  storeNewAudioCacheEntry,
} from '@/utils/audioCache'
import type { AudioCacheCompletionMessage } from '@/utils/audioCache'

declare const self: ServiceWorkerGlobalScope

const AUDIO_CACHE_NAME = 'koel-audio-v1'
const AUDIO_CACHE_COMPLETION_NAME = 'koel-audio-completions-v1'
const STATIC_CACHE_NAME = 'koel-static-v1'

/**
 * Check if a request URL is a static asset (JS, CSS, images, fonts).
 */
const isStaticAsset = (url: string): boolean => {
  try {
    const u = new URL(url)
    if (u.protocol !== 'http:' && u.protocol !== 'https:') return false
    return /\.(js|css|png|jpg|jpeg|svg|gif|ico|woff2?|ttf|eot|otf)(\?.*)?$/.test(u.pathname)
  } catch {
    return false
  }
}

// ---- Fetch handler ----

self.addEventListener('fetch', (event: FetchEvent) => {
  const { request } = event

  if (isCacheablePlayUrl(request.url)) {
    event.respondWith(handlePlayRequest(request))
    return
  }

  if (isStaticAsset(request.url)) {
    event.respondWith(handleStaticAsset(request))
    return
  }
})

/**
 * For audio play requests: serve from cache if available, otherwise fetch from network.
 * Audio is cached under a normalized key (without auth token).
 * Supports HTTP Range requests for seeking in cached audio.
 */
const handlePlayRequest = async (request: Request): Promise<Response> => {
  const cache = await caches.open(AUDIO_CACHE_NAME)
  const cacheKey = normalizeAudioCacheKey(request.url)
  const cached = await cache.match(cacheKey)

  if (cached) {
    return handleCachedAudioRangeRequest(request, cached)
  }

  // Not cached — fetch from network and let it stream through.
  // We do NOT cache on-the-fly here; caching is done proactively via the CACHE_AUDIO message.
  return fetch(request)
}

/**
 * Static assets: network-first for JS (to pick up new deploys), cache-first for images/fonts.
 */
const handleStaticAsset = async (request: Request): Promise<Response> => {
  const url = new URL(request.url)
  const isJS = /\.js(\?.*)?$/.test(url.pathname)

  return isJS ? handleJsAsset(request) : handleOtherStaticAsset(request)
}

/**
 * JS assets: network-first strategy. Try to fetch from network, falling back to cache.
 */
const handleJsAsset = async (request: Request): Promise<Response> => {
  try {
    const response = await fetch(request)
    if (response.ok) {
      const cache = await caches.open(STATIC_CACHE_NAME)
      cache.put(request, response.clone())
    }
    return response
  } catch {
    const cached = await caches.open(STATIC_CACHE_NAME).then(c => c.match(request))
    return cached || new Response('Service Unavailable', { status: 503 })
  }
}

/**
 * Non-JS static assets (images, fonts, CSS): cache-first strategy.
 */
const handleOtherStaticAsset = async (request: Request): Promise<Response> => {
  const cache = await caches.open(STATIC_CACHE_NAME)
  const cached = await cache.match(request)

  if (cached) {
    return cached
  }

  const response = await fetch(request)

  if (response.ok) {
    cache.put(request, response.clone())
  }

  return response
}

// ---- Message handler for proactive audio caching ----

export interface CacheAudioMessage {
  type: 'CACHE_AUDIO'
  songId: string
  sourceUrl: string
  playable?: Playable
}

export interface DeleteAudioCacheMessage {
  type: 'DELETE_AUDIO_CACHE'
  songId: string
  sourceUrl: string
}

export interface GetCacheStatusMessage {
  type: 'GET_CACHE_STATUS'
  sourceUrls: string[]
}

export interface RecoverAudioCacheCompletionsMessage {
  type: 'RECOVER_AUDIO_CACHE_COMPLETIONS'
}

export interface AcknowledgeAudioCacheCompletionMessage {
  type: 'ACK_AUDIO_CACHE_COMPLETION'
  songId: string
  sourceUrl: string
}

type SWMessage =
  | CacheAudioMessage
  | DeleteAudioCacheMessage
  | GetCacheStatusMessage
  | RecoverAudioCacheCompletionsMessage
  | AcknowledgeAudioCacheCompletionMessage

self.addEventListener('message', (event: ExtendableMessageEvent) => {
  const data = event.data as SWMessage
  const client = event.source as Client | null

  switch (data.type) {
    case 'CACHE_AUDIO':
      event.waitUntil(cacheAudio(data, client))
      break

    case 'DELETE_AUDIO_CACHE':
      if (client) {
        event.waitUntil(deleteAudioCache(data, client))
      }
      break

    case 'GET_CACHE_STATUS':
      if (client) {
        event.waitUntil(getCacheStatus(data, client))
      }
      break

    case 'RECOVER_AUDIO_CACHE_COMPLETIONS':
      if (client) {
        event.waitUntil(recoverAudioCacheCompletions(client))
      }
      break

    case 'ACK_AUDIO_CACHE_COMPLETION':
      event.waitUntil(acknowledgeAudioCacheCompletion(data))
      break
  }
})

const postMessageSafely = (client: Client, message: unknown): boolean => {
  try {
    client.postMessage(message)
    return true
  } catch {
    return false
  }
}

const postCacheResult = async (originatingClient: Client | null, message: unknown) => {
  try {
    if (originatingClient) {
      const currentClient = await self.clients.get(originatingClient.id)

      if (currentClient && postMessageSafely(currentClient, message)) {
        return
      }
    }

    const activeWindowClients = await self.clients.matchAll({ includeUncontrolled: true, type: 'window' })
    activeWindowClients.forEach(client => postMessageSafely(client, message))
  } catch {
    return
  }
}

const createCacheProgressReporter = (originatingClient: Client | null) => {
  let client = originatingClient
  let recoveryPromise: Promise<void> | null = null

  return (message: unknown) => {
    if (!client || recoveryPromise) {
      return
    }

    if (postMessageSafely(client, message)) {
      return
    }

    const clientId = client.id
    client = null
    recoveryPromise = self.clients
      .get(clientId)
      .then(async currentClient => {
        client =
          currentClient ?? (await self.clients.matchAll({ includeUncontrolled: true, type: 'window' }))[0] ?? null

        if (client && !postMessageSafely(client, message)) {
          client = null
        }
      })
      .catch(() => {
        client = null
      })
      .finally(() => {
        recoveryPromise = null
      })
  }
}

const getAudioCacheCompletionKey = (songId: Song['id']): string =>
  new URL(`/__koel/audio-cache-completions/${encodeURIComponent(songId)}`, self.location.origin).toString()

const persistAudioCacheCompletion = async (message: AudioCacheCompletionMessage) => {
  const cache = await caches.open(AUDIO_CACHE_COMPLETION_NAME)
  const cacheKey = getAudioCacheCompletionKey(message.songId)
  const storedMessage = createAudioCacheCompletionMessage(message.songId, message.sourceUrl, message.playable)

  await cache.put(
    cacheKey,
    new Response(JSON.stringify(storedMessage), {
      headers: { 'Content-Type': 'application/json' },
    }),
  )
}

const completeAudioCaching = async (
  songId: Song['id'],
  sourceUrl: string,
  playable: Playable | undefined,
  client: Client | null,
) => {
  const completion = createAudioCacheCompletionMessage(songId, sourceUrl, playable)
  await persistAudioCacheCompletion(completion)
  await postCacheResult(client, completion)
}

const recoverAudioCacheCompletions = async (client: Client) => {
  const completionCache = await caches.open(AUDIO_CACHE_COMPLETION_NAME)
  const audioCache = await caches.open(AUDIO_CACHE_NAME)
  const completionRequests = await completionCache.keys()
  const completions: AudioCacheCompletionMessage[] = []

  for (const request of completionRequests) {
    const response = await completionCache.match(request)

    if (!response) {
      continue
    }

    try {
      const storedCompletion = (await response.json()) as AudioCacheCompletionMessage
      const completion = createAudioCacheCompletionMessage(
        storedCompletion.songId,
        storedCompletion.sourceUrl,
        storedCompletion.playable,
      )

      if (await audioCache.match(completion.sourceUrl)) {
        completions.push(completion)
      } else {
        await completionCache.delete(request)
      }
    } catch {
      await completionCache.delete(request)
    }
  }

  postMessageSafely(client, { type: 'AUDIO_CACHE_COMPLETIONS_RECOVERED', completions })
}

const acknowledgeAudioCacheCompletion = async (data: AcknowledgeAudioCacheCompletionMessage) => {
  await deleteAudioCacheCompletionIfMatching(data.songId, data.sourceUrl)
}

const deleteAudioCacheCompletionIfMatching = async (songId: Song['id'], sourceUrl: string) => {
  const completionCache = await caches.open(AUDIO_CACHE_COMPLETION_NAME)
  const completionKey = getAudioCacheCompletionKey(songId)
  const response = await completionCache.match(completionKey)

  if (!response) {
    return
  }

  try {
    const storedCompletion = (await response.json()) as AudioCacheCompletionMessage

    if (isMatchingAudioCacheCompletion(storedCompletion, songId, sourceUrl)) {
      await completionCache.delete(completionKey)
    }
  } catch {
    await completionCache.delete(completionKey)
  }
}

const cacheAudio = async (data: CacheAudioMessage, client: Client | null) => {
  const { songId, sourceUrl } = data
  const cacheKey = normalizeAudioCacheKey(sourceUrl)
  const cache = await caches.open(AUDIO_CACHE_NAME)
  const reportProgress = createCacheProgressReporter(client)

  try {
    const existing = await cache.match(cacheKey)

    if (existing) {
      await completeAudioCaching(songId, cacheKey, data.playable, client)
      return
    }

    const response = await fetch(sourceUrl)

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    // Clone the response so we can read it for progress and also cache it
    const contentLength = Number(response.headers.get('Content-Length') || 0)
    const reader = response.body?.getReader()

    if (!reader) {
      throw new Error('ReadableStream not supported')
    }

    const chunks: BlobPart[] = []
    let received = 0

    while (true) {
      const { done, value } = await reader.read()

      if (done) {
        break
      }

      chunks.push(value)
      received += value.length

      if (contentLength > 0) {
        reportProgress({
          type: 'CACHE_AUDIO_PROGRESS',
          songId,
          progress: received / contentLength,
          received,
          total: contentLength,
        })
      }
    }

    // Reconstruct the response and cache it under the normalized key
    const blob = new Blob(chunks, { type: response.headers.get('Content-Type') || 'audio/mpeg' })
    const cachedResponse = new Response(blob, {
      status: response.status,
      statusText: response.statusText,
      headers: {
        'Content-Type': response.headers.get('Content-Type') || 'audio/mpeg',
        'Content-Length': String(blob.size),
      },
    })

    await storeNewAudioCacheEntry(cache, cacheKey, cachedResponse, () =>
      completeAudioCaching(songId, cacheKey, data.playable, client),
    )
  } catch (error) {
    await postCacheResult(client, {
      type: 'CACHE_AUDIO_ERROR',
      songId,
      error: error instanceof Error ? error.message : 'Unknown error',
    })
  }
}

const deleteAudioCache = async (data: DeleteAudioCacheMessage, client: Client) => {
  const { songId, sourceUrl } = data
  const cacheKey = normalizeAudioCacheKey(sourceUrl)
  const cache = await caches.open(AUDIO_CACHE_NAME)
  const deleted = await cache.delete(cacheKey)
  await deleteAudioCacheCompletionIfMatching(songId, cacheKey)

  client.postMessage({ type: 'DELETE_AUDIO_CACHE_COMPLETE', songId, sourceUrl: cacheKey, deleted })
}

const getCacheStatus = async (data: GetCacheStatusMessage, client: Client) => {
  const cache = await caches.open(AUDIO_CACHE_NAME)
  const statuses: Record<string, boolean> = {}

  for (const url of data.sourceUrls) {
    const cacheKey = normalizeAudioCacheKey(url)
    const match = await cache.match(cacheKey)
    statuses[url] = Boolean(match)
  }

  client.postMessage({ type: 'CACHE_STATUS', statuses })
}

// ---- Lifecycle ----

self.addEventListener('install', () => {
  self.skipWaiting()
})

self.addEventListener('activate', (event: ExtendableEvent) => {
  // Clean up old caches from the previous Workbox-based SW
  event.waitUntil(
    caches
      .keys()
      .then(names =>
        Promise.all(
          names
            .filter(
              name => name !== AUDIO_CACHE_NAME && name !== AUDIO_CACHE_COMPLETION_NAME && name !== STATIC_CACHE_NAME,
            )
            .map(name => caches.delete(name)),
        ),
      )
      .then(() => self.clients.claim()),
  )
})
