import type { QueuePlaybackService } from '@/services/QueuePlaybackService'
import { playbackService as queuePlayback } from '@/services/QueuePlaybackService'
import type { RadioPlaybackService } from '@/services/RadioPlaybackService'
import { playbackService as radioPlayback } from '@/services/RadioPlaybackService'
import type { BasePlaybackService } from '@/services/BasePlaybackService'

const playbackServiceMap: Record<string, BasePlaybackService> = {
  queue: queuePlayback,
  radio: radioPlayback,
}

export const playbackManager = {
  _currentService: null as BasePlaybackService | null,

  usePlayback(type: keyof typeof playbackServiceMap, mediaElement?: HTMLMediaElement) {
    for (const key in playbackServiceMap) {
      if (key !== type) {
        playbackServiceMap[key].deactivate()
      }
    }

    this._currentService = playbackServiceMap[type]

    return playbackServiceMap[type].activate(mediaElement ?? document.querySelector<HTMLMediaElement>('#audio-player')!)
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
    return playbackManager._currentService as PlaybackTypeMap[T]
  }

  throw new Error(`Unknown playback type: ${type}`)
}
