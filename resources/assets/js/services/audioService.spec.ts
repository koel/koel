import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { audioService, dbToGain } from './audioService'

describe('audioService', () => {
  createHarness({
    afterEach: () => {
      audioService.context = null!
      audioService.source = null!
      audioService.sourceNodes = new WeakMap()
      audioService.element = null!
      audioService.preampGainNode = null!
    },
  })

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

  it('reuses each media element source node when reconnecting the audio graph', () => {
    const originalMedia = document.createElement('audio')
    const replacementMedia = document.createElement('audio')
    const initialSource = {
      connect: vi.fn(),
      disconnect: vi.fn(),
    } as unknown as MediaElementAudioSourceNode
    const originalSource = {
      connect: vi.fn(),
      disconnect: vi.fn(),
    } as unknown as MediaElementAudioSourceNode
    const replacementSource = {
      connect: vi.fn(),
      disconnect: vi.fn(),
    } as unknown as MediaElementAudioSourceNode
    const preampGainNode = {} as GainNode
    const createMediaElementSource = vi.fn((media: HTMLMediaElement) =>
      media === originalMedia ? originalSource : replacementSource,
    )

    audioService.context = { createMediaElementSource } as unknown as AudioContext
    audioService.source = initialSource
    audioService.sourceNodes = new WeakMap()
    audioService.preampGainNode = preampGainNode

    audioService.reconnectSource(originalMedia)
    audioService.reconnectSource(replacementMedia)
    audioService.reconnectSource(originalMedia)
    audioService.reconnectSource(replacementMedia)

    expect(createMediaElementSource).toHaveBeenCalledTimes(2)
    expect(createMediaElementSource).toHaveBeenNthCalledWith(1, originalMedia)
    expect(createMediaElementSource).toHaveBeenNthCalledWith(2, replacementMedia)
    expect(initialSource.disconnect).toHaveBeenCalledTimes(1)
    expect(originalSource.disconnect).toHaveBeenCalledTimes(2)
    expect(replacementSource.disconnect).toHaveBeenCalledTimes(1)
    expect(originalSource.connect).toHaveBeenCalledTimes(2)
    expect(originalSource.connect).toHaveBeenCalledWith(preampGainNode)
    expect(replacementSource.connect).toHaveBeenCalledTimes(2)
    expect(replacementSource.connect).toHaveBeenCalledWith(preampGainNode)
    expect(audioService.source).toBe(replacementSource)
    expect(audioService.element).toBe(replacementMedia)
  })
})
