import { playableStore } from '@/stores/playableStore'
import { logger } from '@/utils/logger'

interface CrossfadeState {
  /** The secondary audio element for the incoming track */
  incomingAudio: HTMLAudioElement
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
   * Start crossfading. The incoming track plays through a standalone audio element
   * with volume controlled directly. The outgoing track is faded out by the caller.
   */
  start(nextPlayable: Playable, duration: number, currentVolume: number): boolean {
    this.cancel()

    try {
      const incomingAudio = document.createElement('audio')
      incomingAudio.crossOrigin = 'anonymous'
      incomingAudio.src = playableStore.getSourceUrl(nextPlayable)
      incomingAudio.volume = 0

      const state: CrossfadeState = {
        incomingAudio,
        playable: nextPlayable,
        intervalId: 0,
        originalVolume: currentVolume,
      }

      this.state = state

      incomingAudio
        .play()
        .then(() => {
          const startTime = performance.now()
          const durationMs = duration * 1000
          const normalizedVolume = currentVolume / 10

          state.intervalId = window.setInterval(() => {
            const elapsed = performance.now() - startTime
            const progress = Math.min(elapsed / durationMs, 1)

            incomingAudio.volume = progress * normalizedVolume

            if (progress >= 1) {
              clearInterval(state.intervalId)
            }
          }, 50)
        })
        .catch(e => {
          logger.warn('Crossfade play failed:', e)
          this.cancel()
        })

      return true
    } catch (e) {
      logger.warn('Crossfade failed to start:', e)
      this.cancel()
      return false
    }
  },

  /**
   * Cancel an active crossfade. Stops the incoming audio and cleans up.
   */
  cancel() {
    if (!this.state) {
      return
    }

    const { incomingAudio, intervalId } = this.state

    clearInterval(intervalId)

    incomingAudio.pause()
    incomingAudio.removeAttribute('src')
    incomingAudio.load()

    this.state = null
  },
}
