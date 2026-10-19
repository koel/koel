import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { equalizerStore } from '@/stores/equalizerStore'
import { audioService, dbToGain } from './audioService'

describe('audioService', () => {
  const h = createHarness()

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

  describe('init', () => {
    it('sets each band to its configured gain in dB', () => {
      const audioNode = () => ({
        connect: vi.fn(),
        gain: { value: 0 },
        Q: { setTargetAtTime: vi.fn() },
        frequency: { setTargetAtTime: vi.fn() },
      })

      vi.stubGlobal(
        'AudioContext',
        class {
          currentTime = 0
          createGain = audioNode
          createBiquadFilter = audioNode
          createAnalyser = audioNode
          createMediaElementSource = audioNode
        },
      )

      const gains = [9, -6, 0, 3, 0, 0, 0, 0, 0, -12]
      h.mock(equalizerStore, 'getConfig').mockReturnValue({ name: null, preamp: 0, gains })
      audioService.bands = []
      h.mock(audioService, 'unlockAudioContext')

      audioService.init(document.createElement('audio'))

      expect(audioService.bands.map(band => band.node.gain.value)).toEqual(gains)
      vi.unstubAllGlobals()
    })
  })

  describe('routeElement', () => {
    it('reuses the source of an element that is routed again', () => {
      const createSource = vi.fn(() => ({ connect: vi.fn(), disconnect: vi.fn() }))
      const firstElement = document.createElement('audio')
      const secondElement = document.createElement('audio')
      audioService.context = { createMediaElementSource: createSource } as unknown as AudioContext
      audioService.sourcesByElement = new WeakMap()
      audioService.source = audioService.getOrCreateSource(firstElement)
      audioService.element = firstElement

      audioService.routeElement(secondElement)
      audioService.routeElement(firstElement)

      expect(createSource).toHaveBeenCalledTimes(2)
      expect(audioService.element).toBe(firstElement)
    })
  })

  describe('changeFilterGain', () => {
    it('sets a band in dB, since that is what a BiquadFilterNode expects', () => {
      const band = { gain: { value: 0 } } as unknown as BiquadFilterNode

      audioService.changeFilterGain(band, 9)

      expect(band.gain.value).toBe(9)
    })
  })

  describe('crossfade routing', () => {
    const makeNode = () => ({
      connect: vi.fn(),
      disconnect: vi.fn(),
      gain: { value: 1, setValueAtTime: vi.fn(), linearRampToValueAtTime: vi.fn() },
    })

    const fakeAudioGraph = () => {
      const sourceNodes: Array<ReturnType<typeof makeNode> & { mediaElement: HTMLMediaElement }> = []

      audioService.context = {
        currentTime: 12,
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

    it('fades the incoming element in through the equalizer to its own normalization gain', () => {
      fakeAudioGraph()
      const incomingAudio = document.createElement('audio')

      audioService.connectCrossfadeElement(incomingAudio, -20, 5)

      const { gain } = audioService.crossfadeGainNode!
      expect(gain.setValueAtTime).toHaveBeenCalledWith(0, 12)
      expect(vi.mocked(gain.linearRampToValueAtTime).mock.calls[0][0]).toBeCloseTo(0.1)
      expect(vi.mocked(gain.linearRampToValueAtTime).mock.calls[0][1]).toBe(17)
      expect(audioService.crossfadeGainNode!.connect).toHaveBeenCalledWith(audioService.preampGainNode)
      expect(audioService.crossfadeSource!.connect).toHaveBeenCalledWith(audioService.crossfadeGainNode)
    })

    it('reuses the incoming element source when it takes over', () => {
      const sourceNodes = fakeAudioGraph()
      const incomingAudio = document.createElement('audio')
      audioService.connectCrossfadeElement(incomingAudio, 0, 5)

      audioService.reconnectSource(incomingAudio)

      expect(sourceNodes).toHaveLength(1)
      expect(audioService.source).toBe(sourceNodes[0])
      expect(audioService.source.connect).toHaveBeenCalledWith(audioService.normalizationGainNode)
      expect(audioService.crossfadeSource).toBeNull()
    })
  })
})
