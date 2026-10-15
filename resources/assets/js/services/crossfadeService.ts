import { playableStore } from '@/stores/playableStore'
import { logger } from '@/utils/logger'
import { audioService } from '@/services/audioService'
import { volumeNormalizer } from '@/services/volumeNormalizer'

interface CrossfadeState {
  /** The secondary audio element for the incoming track */
  incomingAudio: HTMLAudioElement
  /** The playable being faded in */
  playable: Playable
  /** Whether the audio graph fades the incoming track in, instead of its element volume */
  fadesThroughAudioGraph: boolean
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
   * Start crossfading. The incoming track plays through a secondary audio element and fades in over `duration`
   * seconds. The outgoing track is faded out by the caller.
   */
  start(nextPlayable: Playable, duration: number, currentVolume: number): boolean {
    this.cancel()

    const sourceUrl = playableStore.getResolvedSourceUrl(nextPlayable)

    if (!sourceUrl) {
      return false
    }

    try {
      const incomingAudio = document.createElement('audio')
      incomingAudio.crossOrigin = 'anonymous'
      incomingAudio.src = sourceUrl
      incomingAudio.volume = 0

      const fadesThroughAudioGraph = Boolean(audioService.context)

      if (fadesThroughAudioGraph) {
        audioService.connectCrossfadeElement(incomingAudio, volumeNormalizer.computeGainDb(nextPlayable), duration)
      }

      this.state = {
        incomingAudio,
        playable: nextPlayable,
        fadesThroughAudioGraph,
        originalVolume: currentVolume,
      }

      incomingAudio.play().catch(e => {
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
   * Set the incoming track's volume for the crossfade's progress (0 to 1), unless the audio graph fades it in.
   */
  fadeInIncomingTrack(progress: number, volume: number) {
    if (!this.state) {
      return
    }

    const elementVolume = Math.max(0, Math.min(1, volume / 10))

    this.state.incomingAudio.volume = this.state.fadesThroughAudioGraph ? elementVolume : elementVolume * progress
  },

  /**
   * Cancel an active crossfade. Stops the incoming audio and cleans up.
   */
  cancel() {
    if (!this.state) {
      return
    }

    const { incomingAudio } = this.state

    incomingAudio.pause()
    incomingAudio.removeAttribute('src')
    incomingAudio.load()
    audioService.disconnectCrossfadeElement()

    this.state = null
  },
}
