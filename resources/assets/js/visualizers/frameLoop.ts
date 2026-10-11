import { audioService } from '@/services/audioService'

/**
 * Call renderFrame on every animation frame until the returned stop function is called.
 * Frames are skipped while playback is paused, except the first one, so the visualizer is never blank.
 */
export const startFrameLoop = (renderFrame: () => void) => {
  let frameId = 0
  let hasRendered = false

  const tick = () => {
    frameId = requestAnimationFrame(tick)

    if (hasRendered && audioService.element?.paused) {
      return
    }

    renderFrame()
    hasRendered = true
  }

  frameId = requestAnimationFrame(tick)

  return () => cancelAnimationFrame(frameId)
}
