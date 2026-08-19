import type { QueuePlaybackService } from '@/services/QueuePlaybackService'
import { playbackService as queuePlayback } from '@/services/QueuePlaybackService'
import type { RadioPlaybackService } from '@/services/RadioPlaybackService'
import { playbackService as radioPlayback } from '@/services/RadioPlaybackService'
import type { BasePlaybackService } from '@/services/BasePlaybackService'
import { audioService } from '@/services/audioService'

const playbackServiceMap: Record<string, BasePlaybackService> = {
  queue: queuePlayback,
  radio: radioPlayback,
}

export const playbackManager = {
  currentService: null as BasePlaybackService | null,

  usePlayback(type: keyof typeof playbackServiceMap, mediaElement?: HTMLMediaElement) {
    const nextService = playbackServiceMap[type]
    const isSwitchingServices = this.currentService !== nextService

    if (isSwitchingServices) {
      for (const key in playbackServiceMap) {
        if (key !== type) {
          playbackServiceMap[key].deactivate()
        }
      }
    }

    const targetMedia = mediaElement ?? document.querySelector<HTMLMediaElement>('#audio-player')!

    if (isSwitchingServices && nextService.media && nextService.media !== targetMedia) {
      nextService.swapMediaElement(targetMedia)
    }

    this.currentService = nextService
    const activeService = nextService.activate(targetMedia)

    if (audioService.context && audioService.element !== activeService.media) {
      audioService.reconnectSource(activeService.media)
    }

    return activeService
  },

  useQueuePlayback(mediaElement?: HTMLMediaElement) {
    return this.usePlayback('queue', mediaElement)
  },

  useRadioPlayback(mediaElement?: HTMLMediaElement) {
    return this.usePlayback('radio', mediaElement)
  },
}

interface PlaybackTypeMap {
  queue: QueuePlaybackService
  radio: RadioPlaybackService
  current: BasePlaybackService | null
}

export function playback<T extends keyof PlaybackTypeMap = 'queue'>(
  type?: T,
  mediaElement?: HTMLMediaElement,
): PlaybackTypeMap[T] {
  const actualType = (type ?? 'queue') as keyof PlaybackTypeMap

  if (actualType === 'queue') {
    return playbackManager.useQueuePlayback(mediaElement) as PlaybackTypeMap[T]
  } else if (actualType === 'radio') {
    return playbackManager.useRadioPlayback(mediaElement) as PlaybackTypeMap[T]
  } else if (actualType === 'current') {
    return playbackManager.currentService as PlaybackTypeMap[T]
  }

  throw new Error(`Unknown playback type: ${type}`)
}
