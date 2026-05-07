import isMobile from 'ismobilejs'
import { useThrottleFn } from '@vueuse/core'
import { watch } from 'vue'
import { volumeManager } from '@/services/volumeManager'

export abstract class BasePlaybackService {
  public media!: HTMLMediaElement
  private boundMediaEvents = new Set<[string, EventListener, boolean]>()

  public activate(mediaElement: HTMLMediaElement) {
    if (!this.media) {
      this.media = mediaElement
      watch(volumeManager.volume, volume => this.setVolume(volume), { immediate: true })
      this.setMediaSessionActionHandlers()
    }

    if (!this.boundMediaEvents.size) {
      this.addMediaEventListeners()
    }

    return this
  }

  public setVolume(volume: number) {
    // volumeManager uses 0-10 scale, HTMLMediaElement uses 0-1
    this.media.volume = Math.max(0, Math.min(1, volume / 10))
  }

  protected setMediaSessionActionHandlers() {
    if (!navigator.mediaSession) {
      return
    }

    navigator.mediaSession.setActionHandler('play', () => this.resume())
    navigator.mediaSession.setActionHandler('pause', () => this.pause())
    navigator.mediaSession.setActionHandler('stop', () => this.stop())
    navigator.mediaSession.setActionHandler('previoustrack', () => this.playPrev())
    navigator.mediaSession.setActionHandler('nexttrack', () => this.playNext())

    if (!isMobile.apple) {
      navigator.mediaSession.setActionHandler('seekbackward', details => this.rewind(details.seekOffset || 10))
      navigator.mediaSession.setActionHandler('seekforward', details => this.forward(details.seekOffset || 10))
    }

    navigator.mediaSession.setActionHandler('seekto', details => {
      if (details.fastSeek && 'fastSeek' in this.media) {
        this.fastSeek(details.seekTime || 0)
      } else {
        this.seekTo(details.seekTime || 0)
      }
    })
  }

  private addMediaEventListeners() {
    const listen = (event: string, handler: Closure, options?: boolean | AddEventListenerOptions) => {
      this.media.addEventListener(event, handler, options)
      this.boundMediaEvents.add([event, handler, !!options])
    }

    listen('error', this.onError.bind(this), true)
    listen('ended', this.onEnded.bind(this))

    const timeUpdateHandler = window.RUNNING_UNIT_TESTS ? this.onTimeUpdate : useThrottleFn(this.onTimeUpdate, 1000)
    listen('timeupdate', timeUpdateHandler.bind(this))
  }

  /** Move media event listeners to a new element (e.g. after a crossfade element swap) */
  public swapMediaElement(newMedia: HTMLMediaElement) {
    // Remove listeners from old element
    this.boundMediaEvents.forEach(([event, handler, options]) => {
      this.media.removeEventListener(event, handler, options)
    })

    this.boundMediaEvents.clear()

    // Swap the reference
    this.media = newMedia

    // Reattach listeners on the new element
    this.addMediaEventListeners()
  }

  public abstract play(source: Streamable): Promise<void>

  public abstract stop(): Promise<void>

  public abstract pause(): Promise<void>

  public abstract resume(): Promise<void>

  public abstract playNext(): Promise<void>

  public abstract playPrev(): Promise<void>

  public abstract toggle(): Promise<void>

  public abstract rewind(seconds: number): void

  public abstract forward(seconds: number): void

  public abstract seekTo(position: number): void

  public abstract fastSeek(position: number): void

  protected abstract onError(error: ErrorEvent): void

  protected abstract onEnded(event: Event): void

  protected abstract onTimeUpdate(event: Event): void

  public abstract rotateRepeatMode(): void

  public deactivate() {
    this.boundMediaEvents.forEach(([event, handler, options]) => {
      this.media.removeEventListener(event, handler, options)
    })

    this.boundMediaEvents.clear()

    this.stop()
  }
}
