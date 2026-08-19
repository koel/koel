import { logger } from '@/utils/logger'
import { progressiveTranscodingService } from '@/services/progressiveTranscodingService'
import { playbackPreloadService } from '@/services/playbackPreloadService'

const CROSSFADE_READINESS_TIMEOUT = 10_000

export interface CrossfadeState {
  /** The secondary audio element for the incoming track */
  incomingAudio: HTMLAudioElement
  /** The playable being faded in */
  playable: Playable
  /** The requestAnimationFrame handle for the volume ramp */
  rafId: number
  /** The original volume of the primary player (0-10 scale) */
  originalVolume: number
  /** Whether the incoming element uses a progressive transcoding stream */
  progressive: boolean
  /** Whether playback has started successfully */
  ready: boolean
  /** Whether the incoming media failed before it could be promoted */
  failed: boolean
}

interface CrossfadeLifecycle {
  onEnded: EventListener
  onError: EventListener
  onFailure: (() => void) | null
  readinessTimeoutId: number | null
  resolveReadiness: ((ready: boolean) => void) | null
}

const lifecycles = new WeakMap<CrossfadeState, CrossfadeLifecycle>()

const settleReadiness = (state: CrossfadeState, ready: boolean): void => {
  const lifecycle = lifecycles.get(state)

  if (!lifecycle?.resolveReadiness) {
    return
  }

  const resolveReadiness = lifecycle.resolveReadiness
  lifecycle.resolveReadiness = null

  if (lifecycle.readinessTimeoutId !== null) {
    window.clearTimeout(lifecycle.readinessTimeoutId)
    lifecycle.readinessTimeoutId = null
  }

  resolveReadiness(ready)
}

const cleanUpLifecycle = (state: CrossfadeState): void => {
  const lifecycle = lifecycles.get(state)

  if (!lifecycle) {
    return
  }

  if (lifecycle.readinessTimeoutId !== null) {
    window.clearTimeout(lifecycle.readinessTimeoutId)
  }

  state.incomingAudio.removeEventListener('error', lifecycle.onError)
  state.incomingAudio.removeEventListener('ended', lifecycle.onEnded)
  cancelAnimationFrame(state.rafId)
  lifecycles.delete(state)
}

const discardIncomingAudio = (state: CrossfadeState): void => {
  state.incomingAudio.pause()
  state.incomingAudio.removeAttribute('src')
  state.incomingAudio.load()
}

export const crossfadeService = {
  state: null as CrossfadeState | null,

  /** Whether an incoming crossfade has started or is ready */
  get inProgress() {
    return this.state !== null
  },

  /** Whether the incoming crossfade is playing */
  get active() {
    return this.state?.ready ?? false
  },

  /** Whether the current incoming media failed before promotion */
  get failed() {
    return this.state?.failed ?? false
  },

  /**
   * Start crossfading. The incoming track plays through a standalone audio element
   * with volume controlled directly. The outgoing track is faded out by the caller.
   */
  async start(
    nextPlayable: Playable,
    duration: number,
    currentVolume: number,
    onFailure: (() => void) | null = null,
  ): Promise<boolean> {
    this.cancel()

    try {
      const source = progressiveTranscodingService.getSource(nextPlayable)
      const preloadedAudio = source.progressive ? playbackPreloadService.take(nextPlayable) : null
      const incomingAudio = preloadedAudio ?? document.createElement('audio')

      if (!source.progressive) {
        playbackPreloadService.clear()
      }

      incomingAudio.crossOrigin = 'anonymous'

      if (!preloadedAudio) {
        incomingAudio.src = source.url
      }

      incomingAudio.volume = 0

      const state: CrossfadeState = {
        incomingAudio,
        playable: nextPlayable,
        rafId: 0,
        originalVolume: currentVolume,
        progressive: source.progressive,
        ready: false,
        failed: false,
      }

      this.state = state

      const readinessPromise = new Promise<boolean>(resolve => {
        const onIncomingFailure = () => this.fail(state)
        const lifecycle: CrossfadeLifecycle = {
          onEnded: onIncomingFailure,
          onError: onIncomingFailure,
          onFailure,
          readinessTimeoutId: window.setTimeout(() => {
            if (this.state === state) {
              logger.warn('Crossfade playback readiness timed out.')
              this.fail(state)
            }
          }, CROSSFADE_READINESS_TIMEOUT),
          resolveReadiness: resolve,
        }

        lifecycles.set(state, lifecycle)
        incomingAudio.addEventListener('error', lifecycle.onError)
        incomingAudio.addEventListener('ended', lifecycle.onEnded)
      })

      try {
        void incomingAudio.play().then(
          () => settleReadiness(state, true),
          (error: unknown) => {
            if (this.state === state) {
              logger.warn('Crossfade play failed:', error)
              this.fail(state)
            }
          },
        )
      } catch (error: unknown) {
        if (this.state === state) {
          logger.warn('Crossfade play failed:', error)
          this.fail(state)
        }
      }

      const ready = await readinessPromise

      if (!ready || this.state !== state || state.failed) {
        return false
      }

      state.ready = true
      const startTime = performance.now()
      const durationMs = duration * 1000
      const normalizedVolume = currentVolume / 10

      const step = () => {
        if (this.state !== state) {
          return
        }

        const elapsed = performance.now() - startTime
        const progress = Math.min(elapsed / durationMs, 1)

        incomingAudio.volume = progress * normalizedVolume

        if (progress < 1) {
          state.rafId = requestAnimationFrame(step)
        }
      }

      state.rafId = requestAnimationFrame(step)

      return true
    } catch (error: unknown) {
      logger.warn('Crossfade failed to start:', error)
      this.cancel()
      return false
    }
  },

  fail(state: CrossfadeState) {
    if (this.state !== state || state.failed) {
      return
    }

    state.failed = true
    state.ready = false
    settleReadiness(state, false)
    lifecycles.get(state)?.onFailure?.()
    cleanUpLifecycle(state)
    discardIncomingAudio(state)
  },

  consumeReadyState(state: CrossfadeState): CrossfadeState | null {
    if (this.state !== state || !state.ready || state.failed) {
      return null
    }

    this.state = null
    cleanUpLifecycle(state)

    return state
  },

  /**
   * Cancel an in-progress crossfade. Stops the incoming audio and cleans up.
   */
  cancel() {
    if (!this.state) {
      return
    }

    const state = this.state

    this.state = null
    settleReadiness(state, false)
    cleanUpLifecycle(state)
    discardIncomingAudio(state)
  },
}
