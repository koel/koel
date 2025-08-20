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
    return this.pause()
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
    use(radioStationStore.current, station => {
      station.playback_state = 'Paused'

      // Broadcast the updated station state.
      socketService.broadcast('SOCKET_STREAMABLE', station)
    })

    // For radio playback, we simply stop the player and reset the media source.
    if (this.player) {
      this.player.media.pause()
      this.player.media.currentTime = 0
      this.player.media.removeAttribute('src')
    }
  }

  public async playNext () {
    // Not supported for radio playback
  }

  public async playPrev () {
    // Not supported for radio playback
  }

  public async resume () {
    if (!radioStationStore.current) {
      throw new Error('Logic exception: no current radio station.')
    }

    return this.play(radioStationStore.current)
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
