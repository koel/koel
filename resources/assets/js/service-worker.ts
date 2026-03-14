/// <reference lib="webworker" />

declare const self: ServiceWorkerGlobalScope

const AUDIO_CACHE_NAME = 'koel-audio-v1'
const STATIC_CACHE_NAME = 'koel-static-v1'

/**
 * Normalize a play URL to a stable cache key by stripping the auth token query param.
 * e.g. "https://example.com/play/abc123?t=token" -> "https://example.com/play/abc123"
 *      "https://example.com/play/abc123/1?t=token" -> "https://example.com/play/abc123/1"
 */
const normalizeCacheKey = (url: string): string => {
  const u = new URL(url)
  u.searchParams.delete('t')
  return u.toString()
}

/**
 * Check if a request URL is a play (audio streaming) URL.
 */
const isPlayUrl = (url: string): boolean => {
  try {
    const u = new URL(url)
    // matches /play/{id} and /play/{id}/1 (mobile transcoding)
    return /\/play\/[^/]+(\/1)?$/.test(u.pathname)
  } catch {
    return false
  }
}

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

  if (isPlayUrl(request.url)) {
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
  const cacheKey = normalizeCacheKey(request.url)
  const cached = await cache.match(cacheKey)

  if (cached) {
    return handleRangeRequest(request, cached)
  }

  // Not cached — fetch from network and let it stream through.
  // We do NOT cache on-the-fly here; caching is done proactively via the CACHE_AUDIO message.
  return fetch(request)
}

/**
 * Handle Range requests for cached audio to enable seeking.
 * The browser sends Range headers when the user seeks in the audio player.
 */
const handleRangeRequest = async (request: Request, cached: Response): Promise<Response> => {
  const rangeHeader = request.headers.get('Range')

  if (!rangeHeader) {
    return cached
  }

  const blob = await cached.blob()
  const totalSize = blob.size
  const match = rangeHeader.match(/bytes=(\d+)-(\d*)/)

  if (!match) {
    return cached
  }

  const start = Number(match[1])
  const end = match[2] ? Number(match[2]) : totalSize - 1
  const sliced = blob.slice(start, end + 1)

  return new Response(sliced, {
    status: 206,
    statusText: 'Partial Content',
    headers: {
      'Content-Type': cached.headers.get('Content-Type') || 'audio/mpeg',
      'Content-Length': String(sliced.size),
      'Content-Range': `bytes ${start}-${end}/${totalSize}`,
      'Accept-Ranges': 'bytes',
    },
  })
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

type SWMessage = CacheAudioMessage | DeleteAudioCacheMessage | GetCacheStatusMessage

self.addEventListener('message', (event: ExtendableMessageEvent) => {
  const data = event.data as SWMessage

  switch (data.type) {
    case 'CACHE_AUDIO':
      event.waitUntil(cacheAudio(data, event.source as Client))
      break

    case 'DELETE_AUDIO_CACHE':
      event.waitUntil(deleteAudioCache(data, event.source as Client))
      break

    case 'GET_CACHE_STATUS':
      event.waitUntil(getCacheStatus(data, event.source as Client))
      break
  }
})

const cacheAudio = async (data: CacheAudioMessage, client: Client) => {
  const { songId, sourceUrl } = data
  const cacheKey = normalizeCacheKey(sourceUrl)
  const cache = await caches.open(AUDIO_CACHE_NAME)

  // Check if already cached
  const existing = await cache.match(cacheKey)

  if (existing) {
    client.postMessage({ type: 'CACHE_AUDIO_COMPLETE', songId })
    return
  }

  try {
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
        client.postMessage({
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

    await cache.put(cacheKey, cachedResponse)
    client.postMessage({ type: 'CACHE_AUDIO_COMPLETE', songId })
  } catch (error) {
    client.postMessage({
      type: 'CACHE_AUDIO_ERROR',
      songId,
      error: error instanceof Error ? error.message : 'Unknown error',
    })
  }
}

const deleteAudioCache = async (data: DeleteAudioCacheMessage, client: Client) => {
  const { songId, sourceUrl } = data
  const cacheKey = normalizeCacheKey(sourceUrl)
  const cache = await caches.open(AUDIO_CACHE_NAME)
  const deleted = await cache.delete(cacheKey)

  client.postMessage({ type: 'DELETE_AUDIO_CACHE_COMPLETE', songId, deleted })
}

const getCacheStatus = async (data: GetCacheStatusMessage, client: Client) => {
  const cache = await caches.open(AUDIO_CACHE_NAME)
  const statuses: Record<string, boolean> = {}

  for (const url of data.sourceUrls) {
    const cacheKey = normalizeCacheKey(url)
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
            .filter(name => name !== AUDIO_CACHE_NAME && name !== STATIC_CACHE_NAME)
            .map(name => caches.delete(name)),
        ),
      )
      .then(() => self.clients.claim()),
  )
})
