import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { signedUrlService } from './signedUrlService'

const urlExpiringIn = (seconds: number) =>
  `http://test/play/song?expires=${Math.floor(Date.now() / 1000) + seconds}&signature=s`

describe('signedUrlService', () => {
  const h = createHarness({
    afterEach: () => signedUrlService.forget('songs/song/play-url'),
  })

  it('reuses a URL that stays valid for a while', async () => {
    const url = urlExpiringIn(30 * 60)
    const getMock = h.mock(http, 'get').mockResolvedValue({ url })

    expect(await signedUrlService.fetch('songs/song/play-url')).toBe(url)
    expect(await signedUrlService.fetch('songs/song/play-url')).toBe(url)
    expect(getMock).toHaveBeenCalledTimes(1)
  })

  it('sends one request for concurrent lookups of the same URL', async () => {
    const url = urlExpiringIn(30 * 60)
    const getMock = h.mock(http, 'get').mockResolvedValue({ url })

    const urls = await Promise.all([
      signedUrlService.fetch('songs/song/play-url'),
      signedUrlService.fetch('songs/song/play-url'),
    ])

    expect(urls).toEqual([url, url])
    expect(getMock).toHaveBeenCalledTimes(1)
  })

  it('fetches a new URL when the cached one expires soon', async () => {
    const getMock = h
      .mock(http, 'get')
      .mockResolvedValueOnce({ url: urlExpiringIn(5 * 60) })
      .mockResolvedValueOnce({ url: urlExpiringIn(30 * 60) })

    await signedUrlService.fetch('songs/song/play-url')
    await signedUrlService.fetch('songs/song/play-url')

    expect(getMock).toHaveBeenCalledTimes(2)
  })

  it('fetches a new URL when the cached one has expired', async () => {
    const getMock = h.mock(http, 'get').mockResolvedValueOnce({ url: urlExpiringIn(30 * 60) })

    await signedUrlService.fetch('songs/song/play-url')
    vi.setSystemTime(Date.now() + 60 * 60 * 1000)

    const freshUrl = urlExpiringIn(30 * 60)
    getMock.mockResolvedValueOnce({ url: freshUrl })

    const url = await signedUrlService.fetch('songs/song/play-url')

    expect(getMock).toHaveBeenCalledTimes(2)
    expect(url).toBe(freshUrl)
    expect(signedUrlService.hasExpired(url)).toBe(false)

    vi.useRealTimers()
  })

  it.each<[string, boolean]>([
    [urlExpiringIn(-1), true],
    [urlExpiringIn(60), false],
    ['http://test/play/song?t=token', false],
  ])('tells whether %s has expired', (url, expired) => {
    expect(signedUrlService.hasExpired(url)).toBe(expired)
  })
})
