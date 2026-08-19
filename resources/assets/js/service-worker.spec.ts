import { describe, expect, it, vi } from 'vite-plus/test'
import { isCacheablePlayUrl } from '@/utils/isCacheablePlayUrl'
import {
  createAudioCacheCompletionMessage,
  handleCachedAudioRangeRequest,
  isMatchingAudioCacheCompletion,
  storeNewAudioCacheEntry,
} from '@/utils/audioCache'

describe('service worker play URL matching', () => {
  it.each(['https://koel.test/play/song-id?t=token', 'https://koel.test/play/song-id/1?t=token'])(
    'handles legacy play URLs through the audio cache',
    url => {
      expect(isCacheablePlayUrl(url)).toBe(true)
    },
  )

  it('bypasses progressive streams', () => {
    expect(isCacheablePlayUrl('https://koel.test/play/song-id?t=token&progressive=1&codec=opus')).toBe(false)
  })

  it('carries playable metadata and the exact normalized source through cache completion', () => {
    const playable = { id: 'song-id', type: 'songs', title: 'Song' } as unknown as Playable

    expect(
      createAudioCacheCompletionMessage('song-id', 'https://koel.test/play/song-id?t=secret&codec=opus', playable),
    ).toEqual({
      type: 'CACHE_AUDIO_COMPLETE',
      songId: 'song-id',
      sourceUrl: 'https://koel.test/play/song-id?codec=opus',
      playable,
    })
  })

  it('does not match a stale codec key against a newer completion for the same song', () => {
    const completion = createAudioCacheCompletionMessage(
      'song-id',
      'https://koel.test/play/song-id?t=secret&codec=opus',
    )

    expect(
      isMatchingAudioCacheCompletion(completion, 'song-id', 'https://koel.test/play/song-id?t=current&codec=aac'),
    ).toBe(false)
    expect(
      isMatchingAudioCacheCompletion(completion, 'song-id', 'https://koel.test/play/song-id?t=current&codec=opus'),
    ).toBe(true)
  })

  it('rolls back a new audio object when durable completion persistence fails', async () => {
    const persistenceError = new Error('metadata quota exceeded')
    const cache = {
      put: vi.fn().mockResolvedValue(undefined),
      delete: vi.fn().mockResolvedValue(true),
    } as unknown as Cache

    await expect(
      storeNewAudioCacheEntry(cache, 'https://koel.test/play/song-id?codec=opus', new Response('audio'), () =>
        Promise.reject(persistenceError),
      ),
    ).rejects.toBe(persistenceError)

    expect(cache.put).toHaveBeenCalledOnce()
    expect(cache.delete).toHaveBeenCalledWith('https://koel.test/play/song-id?codec=opus')
  })

  it('clamps a bounded range that extends beyond the cached audio', async () => {
    const cached = new Response(new Uint8Array([0, 1, 2, 3, 4]), {
      headers: { 'Content-Type': 'audio/webm' },
    })
    const request = new Request('https://koel.test/play/song-id', { headers: { Range: 'bytes=2-99' } })

    const response = await handleCachedAudioRangeRequest(request, cached)

    expect(response.status).toBe(206)
    expect(response.headers.get('Content-Length')).toBe('3')
    expect(response.headers.get('Content-Range')).toBe('bytes 2-4/5')
    expect([...new Uint8Array(await response.arrayBuffer())]).toEqual([2, 3, 4])
  })

  it.each([
    { bytes: new Uint8Array([0, 1, 2]), range: 'bytes=3-', totalSize: 3 },
    { bytes: new Uint8Array([0, 1, 2]), range: 'bytes=2-1', totalSize: 3 },
    { bytes: new Uint8Array(), range: 'bytes=0-', totalSize: 0 },
  ])('returns 416 for an unsatisfiable $range range', async ({ bytes, range, totalSize }) => {
    const cached = new Response(bytes, { headers: { 'Content-Type': 'audio/webm' } })
    const request = new Request('https://koel.test/play/song-id', { headers: { Range: range } })

    const response = await handleCachedAudioRangeRequest(request, cached)

    expect(response.status).toBe(416)
    expect(response.headers.get('Content-Length')).toBe('0')
    expect(response.headers.get('Content-Range')).toBe(`bytes */${totalSize}`)
    expect((await response.blob()).size).toBe(0)
  })
})
