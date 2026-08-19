export const normalizeAudioCacheKey = (url: string): string => {
  const normalizedUrl = new URL(url)
  normalizedUrl.searchParams.delete('t')

  return normalizedUrl.toString()
}

export const addCurrentAudioToken = (cacheKey: string, currentSourceUrl: string): string => {
  const authenticatedUrl = new URL(normalizeAudioCacheKey(cacheKey))
  const audioToken = new URL(currentSourceUrl).searchParams.get('t')

  if (audioToken !== null) {
    authenticatedUrl.searchParams.set('t', audioToken)
  }

  return authenticatedUrl.toString()
}

export interface AudioCacheCompletionMessage {
  type: 'CACHE_AUDIO_COMPLETE'
  songId: Song['id']
  sourceUrl: string
  playable?: Playable
}

export const createAudioCacheCompletionMessage = (
  songId: Song['id'],
  sourceUrl: string,
  playable?: Playable,
): AudioCacheCompletionMessage => ({
  type: 'CACHE_AUDIO_COMPLETE',
  songId,
  sourceUrl: normalizeAudioCacheKey(sourceUrl),
  playable,
})

export const isMatchingAudioCacheCompletion = (
  completion: AudioCacheCompletionMessage,
  songId: Song['id'],
  sourceUrl: string,
): boolean =>
  completion.songId === songId && normalizeAudioCacheKey(completion.sourceUrl) === normalizeAudioCacheKey(sourceUrl)

export const storeNewAudioCacheEntry = async (
  cache: Cache,
  cacheKey: string,
  response: Response,
  complete: () => Promise<void>,
) => {
  await cache.put(cacheKey, response)

  try {
    await complete()
  } catch (error) {
    await cache.delete(cacheKey).catch(() => false)
    throw error
  }
}

export const handleCachedAudioRangeRequest = async (request: Request, cached: Response): Promise<Response> => {
  const rangeHeader = request.headers.get('Range')

  if (!rangeHeader) {
    return cached
  }

  const blob = await cached.blob()
  const totalSize = blob.size
  const match = rangeHeader.match(/^bytes=(\d+)-(\d*)$/)

  if (!match) {
    return cached
  }

  const start = Number(match[1])
  const requestedEnd = match[2] ? Number(match[2]) : totalSize - 1

  if (!Number.isSafeInteger(start) || start >= totalSize || requestedEnd < start) {
    return new Response(null, {
      status: 416,
      statusText: 'Range Not Satisfiable',
      headers: {
        'Content-Length': '0',
        'Content-Range': `bytes */${totalSize}`,
        'Accept-Ranges': 'bytes',
      },
    })
  }

  const end = Math.min(requestedEnd, totalSize - 1)
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
