import { describe, expect, it } from 'vite-plus/test'
import { addCurrentAudioToken, normalizeAudioCacheKey } from './audioCache'

describe('audio cache URLs', () => {
  it('removes only the authentication token from a codec-aware cache key', () => {
    expect(normalizeAudioCacheKey('https://koel.test/play/song-1?t=secret&codec=opus&time=30')).toBe(
      'https://koel.test/play/song-1?codec=opus&time=30',
    )
  })

  it('adds the current authentication token without changing the cached codec', () => {
    expect(
      addCurrentAudioToken('https://koel.test/play/song-1?codec=aac', 'https://koel.test/play/song-1?t=current-token'),
    ).toBe('https://koel.test/play/song-1?codec=aac&t=current-token')
  })

  it('replaces a legacy persisted token with the current token', () => {
    expect(
      addCurrentAudioToken(
        'https://koel.test/play/song-1?t=expired-token&codec=aac',
        'https://koel.test/play/song-1?t=current-token',
      ),
    ).toBe('https://koel.test/play/song-1?codec=aac&t=current-token')
  })

  it('does not retain a legacy token when the current URL has none', () => {
    expect(
      addCurrentAudioToken('https://koel.test/play/song-1?t=expired-token&codec=aac', 'https://koel.test/play/song-1'),
    ).toBe('https://koel.test/play/song-1?codec=aac')
  })
})
