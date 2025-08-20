import isMobile from 'ismobilejs'
import { throttle } from 'lodash'
import plyr from 'plyr'
import { watch } from 'vue'
import { volumeManager } from '@/services/volumeManager'

export abstract class BasePlaybackService {
  public player!: Plyr
  private boundMediaEvents = new Set<[string, EventListener, boolean]>()

  public activate (plyrWrapper: HTMLElement) {
    if (!this.player) {
      // player and watchers can be kept between init/destroy sessions
      this.player = this.player || plyr.setup(plyrWrapper, { controls: [] })[0]
      watch(volumeManager.volume, volume => this.player.setVolume(volume), { immediate: true })
      this.setMediaSessionActionHandlers()
    }

    if (!this.boundMediaEvents.size) {
      this.addMediaEventListeners()
    }

    return this
  }

  protected setMediaSessionActionHandlers () {
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
      if (details.fastSeek && 'fastSeek' in this.player.media) {
        this.fastSeek(details.seekTime || 0)
      } else {
        this.seekTo(details.seekTime || 0)
      }
    })
  }

  private addMediaEventListeners () {
    const listen = (event: string, handler: Closure, options?: boolean | AddEventListenerOptions) => {
      this.player.media.addEventListener(event, handler, options)
      this.boundMediaEvents.add([event, handler, !!options])
    }

    listen('error', this.onError.bind(this), true)
    listen('ended', this.onEnded.bind(this))

    const timeUpdateHandler = process.env.NODE_ENV === 'test' ? this.onTimeUpdate : throttle(this.onTimeUpdate, 1000)
    listen('timeupdate', timeUpdateHandler.bind(this))
  }

  public abstract play (source: Streamable): Promise<void>

  public abstract stop (): Promise<void>

  public abstract pause (): Promise<void>

  public abstract resume (): Promise<void>

  public abstract playNext (): Promise<void>

  public abstract playPrev (): Promise<void>

  public abstract toggle (): Promise<void>

  public abstract rewind (seconds: number): void

  public abstract forward (seconds: number): void

  public abstract seekTo (position: number): void

  public abstract fastSeek (position: number): void

  protected abstract onError (error: ErrorEvent): void

  protected abstract onEnded (event: Event): void

  protected abstract onTimeUpdate (event: Event): void

  public abstract rotateRepeatMode (): void

  public deactivate () {
    // Upon deactivating (i.e. switching to another playback service) we want to remove all event listeners
    // to not mess up the media events in the other service.
    this.boundMediaEvents.forEach(([event, handler, options]) => {
      this.player.media.removeEventListener(event, handler, options)
    })

    this.boundMediaEvents.clear()

    this.stop()
  }
}
