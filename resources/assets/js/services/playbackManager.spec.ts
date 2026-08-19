import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService as queuePlayback } from '@/services/QueuePlaybackService'
import { playbackService as radioPlayback } from '@/services/RadioPlaybackService'
import { audioService } from '@/services/audioService'
import { playback, playbackManager } from '@/services/playbackManager'

describe('playbackManager', () => {
  const h = createHarness({
    beforeEach: () => {
      queuePlayback.media && queuePlayback.deactivate()
      radioPlayback.media && radioPlayback.deactivate()
      playbackManager.currentService = null
      h.createAudioPlayer()
    },
    afterEach: () => {
      audioService.context = null!
      audioService.element = null!
    },
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

  it('restores the shared media element and audio graph after detached queue playback', () => {
    const sharedMedia = document.querySelector<HTMLMediaElement>('#audio-player')!
    playbackManager.useQueuePlayback(sharedMedia)

    const detachedQueueMedia = document.createElement('audio')
    queuePlayback.swapMediaElement(detachedQueueMedia)
    audioService.context = {} as AudioContext
    audioService.element = detachedQueueMedia

    const reconnectSourceMock = h.mock(audioService, 'reconnectSource', media => {
      audioService.element = media
    })

    playbackManager.useRadioPlayback(sharedMedia)

    expect(radioPlayback.media).toBe(sharedMedia)
    expect(audioService.element).toBe(sharedMedia)
    expect(reconnectSourceMock).toHaveBeenCalledWith(sharedMedia)

    playbackManager.useQueuePlayback(sharedMedia)

    expect(queuePlayback.media).toBe(sharedMedia)
    expect(audioService.element).toBe(sharedMedia)
  })
})
