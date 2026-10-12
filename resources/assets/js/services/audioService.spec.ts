import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { audioService, dbToGain } from './audioService'

describe('audioService', () => {
  createHarness()

  describe('dbToGain', () => {
    it('converts 0 dB to gain of 1', () => {
      expect(dbToGain(0)).toBe(1)
    })

    it('converts positive dB to gain > 1', () => {
      expect(dbToGain(20)).toBeCloseTo(10)
    })

    it('converts negative dB to gain < 1', () => {
      expect(dbToGain(-20)).toBeCloseTo(0.1)
    })

    it('handles -Infinity as 0 gain', () => {
      expect(dbToGain(-Infinity)).toBe(0)
    })
  })

  describe('crossfade routing', () => {
    const makeNode = () => ({ connect: vi.fn(), disconnect: vi.fn(), gain: { value: 1 } })

    const fakeAudioGraph = () => {
      const sourceNodes: Array<ReturnType<typeof makeNode> & { mediaElement: HTMLMediaElement }> = []

      audioService.context = {
        createGain: () => makeNode(),
        createMediaElementSource: (mediaElement: HTMLMediaElement) => {
          const sourceNode = { ...makeNode(), mediaElement }
          sourceNodes.push(sourceNode)

          return sourceNode
        },
      } as unknown as AudioContext

      audioService.source = makeNode() as unknown as MediaElementAudioSourceNode
      audioService.normalizationGainNode = makeNode() as unknown as GainNode
      audioService.preampGainNode = makeNode() as unknown as GainNode

      return sourceNodes
    }

    it('plays the incoming element through the equalizer at its own normalization gain', () => {
      fakeAudioGraph()
      const incomingAudio = document.createElement('audio')

      audioService.connectCrossfadeElement(incomingAudio, -20)

      expect(audioService.crossfadeGainNode!.gain.value).toBeCloseTo(0.1)
      expect(audioService.crossfadeGainNode!.connect).toHaveBeenCalledWith(audioService.preampGainNode)
      expect(audioService.crossfadeSource!.connect).toHaveBeenCalledWith(audioService.crossfadeGainNode)
    })

    it('reuses the incoming element source when it takes over', () => {
      const sourceNodes = fakeAudioGraph()
      const incomingAudio = document.createElement('audio')
      audioService.connectCrossfadeElement(incomingAudio, 0)

      audioService.reconnectSource(incomingAudio)

      expect(sourceNodes).toHaveLength(1)
      expect(audioService.source).toBe(sourceNodes[0])
      expect(audioService.source.connect).toHaveBeenCalledWith(audioService.normalizationGainNode)
      expect(audioService.crossfadeSource).toBeNull()
    })
  })
})
