import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService as queuePlayback } from '@/services/QueuePlaybackService'
import { playbackService as radioPlayback } from '@/services/RadioPlaybackService'
import { playback, playbackManager } from '@/services/playbackManager'

describe('playbackManager', () => {
  const h = createHarness({
    beforeEach: () => h.createAudioPlayer(),
  })

  it('uses Queue playback service', () => {
    expect(playbackManager.useQueuePlayback()).toBe(queuePlayback)
    expect(playback('current')).toBe(queuePlayback)
  })

  it('uses Radio playback service', () => {
    expect(playbackManager.useRadioPlayback()).toBe(radioPlayback)
    expect(playback('current')).toBe(radioPlayback)
  })

  it('provides shortcuts to switch playback services', () => {
    expect(playback('queue')).toBe(queuePlayback)
    expect(playback('radio')).toBe(radioPlayback)
    expect(playback('current')).toBe(radioPlayback)
  })

  it('deactivates other playback services when switching', () => {
    playback('queue')
    const deactivateQueuePlayback = h.mock(queuePlayback, 'deactivate')
    const deactivateRadioPlayback = h.mock(radioPlayback, 'deactivate')

    playback('radio')
    expect(deactivateQueuePlayback).toHaveBeenCalled()

    playback('queue')
    expect(deactivateRadioPlayback).toHaveBeenCalled()
  })
})
