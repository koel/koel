import { progressiveTranscodingService } from '@/services/progressiveTranscodingService'

interface ProgressivePreload {
  media: HTMLAudioElement
  playable: Playable
  onError: EventListener
}

class PlaybackPreloadService {
  private progressivePreload: ProgressivePreload | null = null

  public preload(playable: Playable) {
    const source = progressiveTranscodingService.getSource(playable)

    if (source.progressive && this.progressivePreload?.playable.id === playable.id) {
      return
    }

    this.clear()

    const media = document.createElement('audio')

    if (source.progressive) {
      media.crossOrigin = 'anonymous'
      const onError = () => {
        if (this.progressivePreload?.media === media) {
          this.clear()
        }
      }

      media.addEventListener('error', onError)
      this.progressivePreload = { media, playable, onError }
    }

    playable.preloaded = true
    media.setAttribute('src', source.url)
    media.setAttribute('preload', 'auto')
    media.load()
  }

  public take(playable: Playable) {
    if (!this.progressivePreload) {
      return null
    }

    if (this.progressivePreload.playable.id !== playable.id) {
      this.clear()
      return null
    }

    if (this.progressivePreload.media.error) {
      this.clear()
      return null
    }

    const { media, onError } = this.progressivePreload
    this.progressivePreload = null
    media.removeEventListener('error', onError)
    playable.preloaded = false

    return media
  }

  public clear() {
    if (!this.progressivePreload) {
      return
    }

    const { media, playable, onError } = this.progressivePreload
    this.progressivePreload = null
    media.removeEventListener('error', onError)
    playable.preloaded = false
    media.pause()
    media.removeAttribute('src')
    media.load()
  }
}

export const playbackPreloadService = new PlaybackPreloadService()
