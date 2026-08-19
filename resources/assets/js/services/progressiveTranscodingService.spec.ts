import isMobile from 'ismobilejs'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { progressiveTranscodingService } from '@/services/progressiveTranscodingService'
import { useOfflinePlayback } from '@/composables/useOfflinePlayback'

describe('progressiveTranscodingService', () => {
  const { cachedSongIds, manifestEntries } = useOfflinePlayback()
  const h = createHarness({
    beforeEach: () => {
      cachedSongIds.value.clear()
      manifestEntries.value = []
      isMobile.any = false
      commonStore.state.cdn_url = 'http://test/'
      commonStore.state.supports_progressive_transcoding = true
      h.setReadOnlyProperty(navigator, 'onLine', true)
      h.mock(HTMLMediaElement.prototype, 'canPlayType', 'probably')
    },
  })

  it('builds a progressive URL for an eligible song', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const source = progressiveTranscodingService.getSource(song, 123.5)

    expect(source.progressive).toBe(true)
    expect(source.url).toContain(`/play/${song.id}?`)
    expect(source.url).toContain('progressive=1')
    expect(source.url).toContain('time=123.5')
  })

  it('omits the time parameter when playback starts at the beginning', () => {
    const song = h.factory('song').make({ requires_transcoding: true })

    expect(progressiveTranscodingService.getSource(song).url).not.toContain('time=')
  })

  it('uses the canonical cached URL for a song available offline', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const canonicalUrl = `http://test/play/${song.id}?t=null`
    cachedSongIds.value.add(song.id)
    manifestEntries.value = [{ playable: song, cachedAt: Date.now(), size: 0 }]

    const source = progressiveTranscodingService.getSource(song)

    expect(source).toEqual({ url: canonicalUrl, progressive: false })
    expect(progressiveTranscodingService.isEligible(song)).toBe(false)
  })

  it('uses the persisted source URL for offline playback', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    const cachedSourceUrl = `http://test/play/${song.id}?cached=1`
    cachedSongIds.value.add(song.id)
    manifestEntries.value = [{ playable: song, cachedAt: Date.now(), size: 0, sourceUrl: cachedSourceUrl }]

    expect(progressiveTranscodingService.getSource(song)).toEqual({
      url: `${cachedSourceUrl}&t=null`,
      progressive: false,
    })
  })

  it.each<[string, () => unknown]>([
    ['server support is disabled', () => (commonStore.state.supports_progressive_transcoding = false)],
    ['the song does not require transcoding', () => undefined],
    ['the browser is offline', () => h.setReadOnlyProperty(navigator, 'onLine', false)],
    ['the browser cannot play WebM Opus', () => h.mock(HTMLMediaElement.prototype, 'canPlayType', '')],
  ])('uses the plain URL when %s', (_description, arrange) => {
    arrange()
    const song = h.factory('song').make({ requires_transcoding: false })

    if (_description !== 'the song does not require transcoding') {
      song.requires_transcoding = true
    }

    const source = progressiveTranscodingService.getSource(song, 50)

    expect(source.progressive).toBe(false)
    expect(source.url).not.toContain('progressive=1')
  })

  it('uses the existing forced-transcoding mobile URL on mobile', () => {
    const song = h.factory('song').make({ requires_transcoding: true })
    isMobile.any = true
    preferenceStore.temporary.transcode_on_mobile = true

    const source = progressiveTranscodingService.getSource(song, 50)

    expect(source.progressive).toBe(false)
    expect(source.url).toContain(`/play/${song.id}/1?`)
    expect(source.url).not.toContain('progressive=1')
  })

  it('does not use progressive transcoding for podcast episodes', () => {
    const episode = h.factory('episode').make()

    expect(progressiveTranscodingService.getSource(episode).progressive).toBe(false)
  })
})
