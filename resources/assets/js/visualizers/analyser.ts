import { audioService } from '@/services/audioService'

const WEB_AUDIO_DEFAULT_SMOOTHING = 0.8

/**
 * Every visualizer shares the one analyser node, so each must set all of its options,
 * or it inherits whatever the previously opened visualizer left behind.
 */
export const configureAnalyser = ({ fftSize, smoothing = WEB_AUDIO_DEFAULT_SMOOTHING }: {
  fftSize: number
  smoothing?: number
}) => {
  const analyser = audioService.analyzer
  analyser.fftSize = fftSize
  analyser.smoothingTimeConstant = smoothing

  return analyser
}
