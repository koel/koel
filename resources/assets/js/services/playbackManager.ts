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

  usePlayback (type: keyof typeof playbackServiceMap, plyrWrapper?: HTMLElement) {
    for (const key in playbackServiceMap) {
      if (key !== type) {
        playbackServiceMap[key].deactivate()
      }
    }

    this._currentService = playbackServiceMap[type]

    return playbackServiceMap[type].activate(plyrWrapper ?? document.querySelector('.plyr-wrapper') as HTMLElement)
  },

  useQueuePlayback (plyrWrapper?: HTMLElement) {
    return this.usePlayback('queue', plyrWrapper)
  },

  useRadioPlayback (plyrWrapper?: HTMLElement) {
    return this.usePlayback('radio', plyrWrapper)
  },
}

interface PlaybackTypeMap {
  queue: QueuePlaybackService
  radio: RadioPlaybackService
  current: BasePlaybackService | null
}

export function playback<
  T extends keyof PlaybackTypeMap = 'queue',
> (type?: T, plyrWrapper?: HTMLElement): PlaybackTypeMap[T] {
  const actualType = (type ?? 'queue') as keyof PlaybackTypeMap

  if (actualType === 'queue') {
    return playbackManager.useQueuePlayback(plyrWrapper) as PlaybackTypeMap[T]
  } else if (actualType === 'radio') {
    return playbackManager.useRadioPlayback(plyrWrapper) as PlaybackTypeMap[T]
  } else if (actualType === 'current') {
    return playbackManager._currentService as PlaybackTypeMap[T]
  }

  throw new Error(`Unknown playback type: ${type}`)
}
