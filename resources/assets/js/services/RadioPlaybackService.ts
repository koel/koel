import { BasePlaybackService } from '@/services/BasePlaybackService'
import { radioStationStore } from '@/stores/radioStationStore'
import { use } from '@/utils/helpers'
import { socketService } from '@/services/socketService'

export class RadioPlaybackService extends BasePlaybackService {
  public async play (station: RadioStation) {
    use(radioStationStore.current, station => station.playback_state = 'Stopped')

    station.playback_state = 'Playing'
    this.player.media.src = radioStationStore.getSourceUrl(station)
    await this.player.media.play()

    socketService.broadcast('SOCKET_STREAMABLE', station)
  }

  public async stop () {
    // Even though we're stopping playback, we still want to set the playback state to 'Paused'.
    // This allows for resuming playback later, if requested.
    use(radioStationStore.current, station => station.playback_state = 'Paused')

    if (this.player) {
      this.player.media.pause()
      this.player.media.currentTime = 0
      this.player.media.removeAttribute('src')
    }

    socketService.broadcast('SOCKET_PLAYBACK_STOPPED')
  }

  protected onError (): void { // eslint-disable node/handle-callback-err
    // @todo Handle radio playback errors?
  }

  public fastSeek (): void {
    // Not supported for radio playback
  }

  public forward (): void {
    // Not supported for radio playback
  }

  protected onEnded (): void {
    // Not supported for radio playback
  }

  protected onTimeUpdate (): void {
    // Not supported for radio playback
  }

  public async pause () {
    // For radio playback, we simply stop instead of pausing.
    return this.stop()
  }

  public async playNext () {
    // Not supported for radio playback
  }

  public async playPrev () {
    // Not supported for radio playback
  }

  public async resume () {
    return this.stop()
  }

  public rewind (): void {
    // Not supported for radio playback
  }

  public seekTo (): void {
    // Not supported for radio playback
  }

  public rotateRepeatMode (): void {
    // Not supported for radio playback
  }

  async toggle () {
    if (radioStationStore.current?.playback_state === 'Playing') {
      await this.stop()
    } else {
      await this.play(radioStationStore.current!)
    }
  }
}

export const playbackService = new RadioPlaybackService()
