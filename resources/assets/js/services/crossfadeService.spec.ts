import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playableStore } from '@/stores/playableStore'
import { audioService } from '@/services/audioService'
import { crossfadeService } from './crossfadeService'

describe('crossfadeService', () => {
  const h = createHarness({
    afterEach: () => {
      crossfadeService.cancel()
      audioService.context = null!
    },
  })

  it('skips the crossfade when the next playable has no source URL yet', () => {
    h.mock(playableStore, 'getResolvedSourceUrl').mockReturnValue(undefined)

    expect(crossfadeService.start(h.factory('song').make(), 5, 7)).toBe(false)
    expect(crossfadeService.active).toBe(false)
  })

  it('fades the incoming element in with the crossfade progress without an audio graph', () => {
    h.mock(playableStore, 'getResolvedSourceUrl').mockReturnValue('http://test/play/next')
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)

    crossfadeService.start(h.factory('song').make(), 5, 8)
    const { incomingAudio } = crossfadeService.state!

    expect(incomingAudio.volume).toBe(0)

    crossfadeService.fadeInIncomingTrack(0.5, 8)

    expect(incomingAudio.volume).toBeCloseTo(0.4)
  })

  it('leaves the fade-in to the audio graph when there is one', () => {
    h.mock(playableStore, 'getResolvedSourceUrl').mockReturnValue('http://test/play/next')
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    audioService.context = {} as AudioContext
    const connectMock = h.mock(audioService, 'connectCrossfadeElement')
    h.mock(audioService, 'disconnectCrossfadeElement')

    crossfadeService.start(h.factory('song').make(), 4.5, 8)
    const { incomingAudio } = crossfadeService.state!

    expect(connectMock).toHaveBeenCalledWith(incomingAudio, 0, 4.5)

    crossfadeService.fadeInIncomingTrack(0.1, 8)

    expect(incomingAudio.volume).toBeCloseTo(0.8)
  })
})
