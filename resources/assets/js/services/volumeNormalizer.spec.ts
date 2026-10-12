import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore } from '@/stores/preferenceStore'
import { audioService } from '@/services/audioService'
import { volumeNormalizer } from './volumeNormalizer'

describe('volumeNormalizer', () => {
  const h = createHarness({
    beforeEach: () => {
      preferenceStore.state.normalize_volume = true
    },
  })

  it('turns a loud song down to the target loudness', async () => {
    const song = h.factory('song').make({ loudness: -8, true_peak: 1.5 })

    await h.withPlusEdition(() => expect(volumeNormalizer.computeGainDb(song)).toBe(-6))
  })

  it('turns a quiet song up to the target loudness when it has the headroom', async () => {
    const song = h.factory('song').make({ loudness: -20, true_peak: -10 })

    await h.withPlusEdition(() => expect(volumeNormalizer.computeGainDb(song)).toBe(6))
  })

  it('turns a quiet song up only as far as its true peak allows', async () => {
    const song = h.factory('song').make({ loudness: -20, true_peak: -2.5 })

    await h.withPlusEdition(() => expect(volumeNormalizer.computeGainDb(song)).toBe(1.5))
  })

  it('never turns a quiet song down to make up for a high true peak', async () => {
    const song = h.factory('song').make({ loudness: -20, true_peak: 2 })

    await h.withPlusEdition(() => expect(volumeNormalizer.computeGainDb(song)).toBe(0))
  })

  it('leaves an unanalyzed song alone', async () => {
    const song = h.factory('song').make({ loudness: null, true_peak: null })

    await h.withPlusEdition(() => expect(volumeNormalizer.computeGainDb(song)).toBe(0))
  })

  it('leaves episodes alone', async () => {
    const episode = h.factory('episode').make()

    await h.withPlusEdition(() => expect(volumeNormalizer.computeGainDb(episode)).toBe(0))
  })

  it('leaves songs alone when turned off', async () => {
    preferenceStore.state.normalize_volume = false
    const song = h.factory('song').make({ loudness: -8, true_peak: 1.5 })

    await h.withPlusEdition(() => expect(volumeNormalizer.computeGainDb(song)).toBe(0))
  })

  it('leaves songs alone without Koel Plus', () => {
    const song = h.factory('song').make({ loudness: -8, true_peak: 1.5 })

    expect(volumeNormalizer.computeGainDb(song)).toBe(0)
  })

  it('applies the gain to the audio graph', async () => {
    const song = h.factory('song').make({ loudness: -8, true_peak: 1.5 })
    const originalContext = audioService.context
    audioService.context = {} as AudioContext
    const changeGainMock = h.mock(audioService, 'changeNormalizationGain')

    await h.withPlusEdition(() => volumeNormalizer.applyToStreamable(song))

    expect(changeGainMock).toHaveBeenCalledWith(-6)
    audioService.context = originalContext
  })
})
