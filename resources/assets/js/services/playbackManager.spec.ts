import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import { playbackService as queuePlayback } from '@/services/QueuePlaybackService'
import { playbackService as radioPlayback } from '@/services/RadioPlaybackService'
import { playback, playbackManager } from '@/services/playbackManager'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => this.createAudioPlayer())
  }

  protected test () {
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
      const deactivateQueuePlayback = this.mock(queuePlayback, 'deactivate')
      const deactivateRadioPlayback = this.mock(radioPlayback, 'deactivate')

      playback('radio')
      expect(deactivateQueuePlayback).toHaveBeenCalled()

      playback('queue')
      expect(deactivateRadioPlayback).toHaveBeenCalled()
    })
  }
}
