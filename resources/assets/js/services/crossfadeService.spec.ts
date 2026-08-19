import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { crossfadeService } from '@/services/crossfadeService'
import { playbackService } from '@/services/QueuePlaybackService'

describe('crossfadeService', () => {
  const h = createHarness({
    beforeEach: () => {
      commonStore.state.cdn_url = 'http://test/'
      commonStore.state.supports_progressive_transcoding = true
      h.setReadOnlyProperty(navigator, 'onLine', true)
      h.mock(HTMLMediaElement.prototype, 'canPlayType', 'probably')
    },
    afterEach: () => {
      crossfadeService.cancel()
      vi.useRealTimers()
    },
  })

  it('uses a progressive source for an eligible incoming song', async () => {
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    const song = h.factory('song').make({ requires_transcoding: true })

    await expect(crossfadeService.start(song, 5, 7)).resolves.toBe(true)
    expect(crossfadeService.state?.progressive).toBe(true)
    expect(crossfadeService.state?.ready).toBe(true)
    expect(crossfadeService.state?.incomingAudio.src).toContain('progressive=1')
  })

  it('reports readiness only after incoming playback starts', async () => {
    let resolvePlay!: () => void
    h.mock(HTMLMediaElement.prototype, 'play').mockReturnValueOnce(
      new Promise<void>(resolve => (resolvePlay = resolve)),
    )
    const song = h.factory('song').make({ requires_transcoding: true })

    const startPromise = crossfadeService.start(song, 5, 7)

    expect(crossfadeService.inProgress).toBe(true)
    expect(crossfadeService.active).toBe(false)
    expect(crossfadeService.state?.ready).toBe(false)

    resolvePlay()

    await expect(startPromise).resolves.toBe(true)
    expect(crossfadeService.inProgress).toBe(true)
    expect(crossfadeService.active).toBe(true)
    expect(crossfadeService.state?.ready).toBe(true)
  })

  it('bounds incoming playback readiness when play never settles', async () => {
    vi.useFakeTimers()
    h.mock(HTMLMediaElement.prototype, 'play').mockReturnValueOnce(new Promise<void>(() => undefined))
    const song = h.factory('song').make({ requires_transcoding: true })
    let result: boolean | undefined

    void crossfadeService.start(song, 5, 7).then(started => {
      result = started
    })
    await vi.runAllTimersAsync()

    expect(result).toBe(false)
    expect(crossfadeService.active).toBe(false)
  })

  it.each(['error', 'ended'])('invalidates ready incoming playback on %s before promotion', async eventName => {
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    const song = h.factory('song').make({ requires_transcoding: true })

    await expect(crossfadeService.start(song, 5, 7)).resolves.toBe(true)
    const incomingAudio = crossfadeService.state!.incomingAudio
    incomingAudio.dispatchEvent(new Event(eventName))

    expect(crossfadeService.active).toBe(false)
    expect(incomingAudio.src).toBe('')
  })

  it('reuses a preloaded progressive stream for crossfade playback', async () => {
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    const song = h.factory('song').make({ requires_transcoding: true })
    const originalCreateElement = document.createElement.bind(document)
    const createdAudioElements: HTMLAudioElement[] = []

    h.mock(document, 'createElement', (tagName: string) => {
      const element = originalCreateElement(tagName)

      if (element instanceof HTMLAudioElement) {
        createdAudioElements.push(element)
      }

      return element
    })

    playbackService.preload(song)
    const preloadedAudio = createdAudioElements.find(media => media.src.includes('progressive=1'))!

    await expect(crossfadeService.start(song, 5, 7)).resolves.toBe(true)
    expect(crossfadeService.state?.incomingAudio).toBe(preloadedAudio)
    expect(song.preloaded).toBe(false)
  })

  it('disposes a retained progressive preload before crossfading to a nonprogressive song', async () => {
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    const preloadedSong = h.factory('song').make({ requires_transcoding: true })
    const crossfadedSong = h.factory('song').make({ requires_transcoding: false })
    const originalCreateElement = document.createElement.bind(document)
    const createdAudioElements: HTMLAudioElement[] = []

    h.mock(document, 'createElement', (tagName: string) => {
      const element = originalCreateElement(tagName)

      if (element instanceof HTMLAudioElement) {
        createdAudioElements.push(element)
      }

      return element
    })

    playbackService.preload(preloadedSong)
    const preloadedAudio = createdAudioElements.find(media => media.src.includes(preloadedSong.id))!

    await expect(crossfadeService.start(crossfadedSong, 5, 7)).resolves.toBe(true)
    expect(preloadedAudio.src).toBe('')
    expect(preloadedSong.preloaded).toBe(false)
    expect(crossfadeService.state?.incomingAudio).not.toBe(preloadedAudio)
    expect(crossfadeService.state?.incomingAudio.src).toContain(crossfadedSong.id)
  })
})
