import isMobile from 'ismobilejs'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { isSong } from '@/utils/typeGuards'
import { getCachedOfflineSourceUrl, isPlayableCachedForOffline } from '@/composables/useOfflinePlayback'

const OPUS_MEDIA_TYPE = 'audio/webm; codecs="opus"'

export interface PlaybackSource {
  url: string
  progressive: boolean
}

export const progressiveTranscodingService = {
  isEligible(playable: Playable): boolean {
    if (
      !commonStore.state.supports_progressive_transcoding ||
      isMobile.any ||
      !isSong(playable) ||
      !playable.requires_transcoding ||
      isPlayableCachedForOffline(playable) ||
      !navigator.onLine
    ) {
      return false
    }

    return document.createElement('audio').canPlayType(OPUS_MEDIA_TYPE) !== ''
  },

  getSource(playable: Playable, position = 0): PlaybackSource {
    const url = playableStore.getSourceUrl(playable)
    const cachedUrl = getCachedOfflineSourceUrl(playable)

    if (cachedUrl) {
      return { url: cachedUrl, progressive: false }
    }

    if (!this.isEligible(playable)) {
      return { url, progressive: false }
    }

    const separator = url.includes('?') ? '&' : '?'
    const startTime = Math.max(0, position)
    const timeParameter = startTime > 0 ? `&time=${encodeURIComponent(startTime)}` : ''

    return {
      url: `${url}${separator}progressive=1${timeParameter}`,
      progressive: true,
    }
  },
}
