import { isAudioContextSupported } from '@/utils/supports'
import { audioService } from '@/services/audioService'
import { playableStore } from '@/stores/playableStore'
import { logger } from '@/utils/logger'

interface CrossfadeState {
  /** The secondary audio element for the incoming track */
  incomingAudio: HTMLAudioElement
  /** MediaElementAudioSourceNode for the incoming track (if AudioContext available) */
  incomingSource: MediaElementAudioSourceNode | null
  /** GainNode to control incoming volume via Web Audio API */
  incomingGain: GainNode | null
  /** The playable being faded in */
  playable: Playable
  /** The animation frame / interval ID for the volume ramp */
  intervalId: number
  /** The original volume of the primary player (0-10 scale) */
  originalVolume: number
}

export const crossfadeService = {
  state: null as CrossfadeState | null,

  /** Whether a crossfade is currently in progress */
  get active() {
    return this.state !== null
  },

  /**
   * Start crossfading. The incoming track is routed through the equalizer chain
   * via its own GainNode so it has the same audio processing as the primary track.
   */
  start(nextPlayable: Playable, duration: number, currentVolume: number): boolean {
    this.cancel()

    try {
      const incomingAudio = document.createElement('audio')
      incomingAudio.crossOrigin = 'anonymous'
      incomingAudio.src = playableStore.getSourceUrl(nextPlayable)

      let incomingSource: MediaElementAudioSourceNode | null = null
      let incomingGain: GainNode | null = null

      if (isAudioContextSupported && audioService.context) {
        // Route through the equalizer: incomingSource → incomingGain → preampGainNode → [EQ chain]
        incomingSource = audioService.context.createMediaElementSource(incomingAudio)
        incomingGain = audioService.context.createGain()
        incomingGain.gain.value = 0

        incomingSource.connect(incomingGain)
        incomingGain.connect(audioService.preampGainNode)

        // Volume is controlled via gainNode, so keep element volume at max
        incomingAudio.volume = 1
      } else {
        // Fallback: control volume directly on the element
        incomingAudio.volume = 0
      }

      const state: CrossfadeState = {
        incomingAudio,
        incomingSource,
        incomingGain,
        playable: nextPlayable,
        intervalId: 0,
        originalVolume: currentVolume,
      }

      this.state = state

      incomingAudio.play().catch(e => logger.warn('Crossfade play failed:', e))

      const startTime = performance.now()
      const durationMs = duration * 1000

      state.intervalId = window.setInterval(() => {
        const elapsed = performance.now() - startTime
        const progress = Math.min(elapsed / durationMs, 1)

        if (incomingGain) {
          incomingGain.gain.value = progress
        } else {
          incomingAudio.volume = progress * (currentVolume / 10)
        }

        if (progress >= 1) {
          clearInterval(state.intervalId)
        }
      }, 50)

      return true
    } catch (e) {
      logger.warn('Crossfade failed to start:', e)
      this.cancel()
      return false
    }
  },

  /**
   * Finalize: disconnect the incoming gain node (the source will be reconnected directly by the caller).
   */
  disconnectIncomingGain() {
    if (!this.state) {
      return
    }

    const { incomingGain, incomingSource } = this.state

    if (incomingGain && incomingSource) {
      try {
        incomingSource.disconnect(incomingGain)
        incomingGain.disconnect()
      } catch {
        // may already be disconnected
      }
    }
  },

  /**
   * Cancel an active crossfade. Stops the incoming audio and cleans up.
   */
  cancel() {
    if (!this.state) {
      return
    }

    const { incomingAudio, incomingGain, incomingSource, intervalId } = this.state

    clearInterval(intervalId)

    if (incomingGain && incomingSource) {
      try {
        incomingSource.disconnect()
        incomingGain.disconnect()
      } catch {
        // may already be disconnected
      }
    }

    incomingAudio.pause()
    incomingAudio.removeAttribute('src')
    incomingAudio.load()

    this.state = null
  },
}
