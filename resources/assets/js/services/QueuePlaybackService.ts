import type { Ref } from 'vue'
import { ref } from 'vue'
import { shuffle } from 'lodash'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { queueStore } from '@/stores/queueStore'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { playableStore } from '@/stores/playableStore'
import { userStore } from '@/stores/userStore'
import { logger } from '@/utils/logger'
import { isEpisode, isSong } from '@/utils/typeGuards'
import { arrayify, getPlayableProp } from '@/utils/helpers'
import { eventBus } from '@/utils/eventBus'
import { isAudioContextSupported } from '@/utils/supports'
import { audioService } from '@/services/audioService'
import { http } from '@/services/http'
import { socketService } from '@/services/socketService'
import { useEpisodeProgressTracking } from '@/composables/useEpisodeProgressTracking'
import { BasePlaybackService } from '@/services/BasePlaybackService'

/**
 * The number of seconds before the current playable ends to start preloading the next one.
 */
const PRELOAD_BUFFER = 30

export class QueuePlaybackService extends BasePlaybackService {
  private repeatModes: RepeatMode[] = ['NO_REPEAT', 'REPEAT_ALL', 'REPEAT_ONE']
  private upNext: Ref<Playable | null> = ref(null)

  /**
   * The next item in the queue.
   * If we're in REPEAT_ALL mode and there's no next item, just get the first item.
   */
  public get next () {
    if (queueStore.next) {
      return queueStore.next
    }

    if (preferences.repeat_mode === 'REPEAT_ALL') {
      return queueStore.first
    }
  }

  /**
   * The previous item in the queue.
   * If we're in REPEAT_ALL mode and there's no prev item, get the last item.
   */
  public get previous () {
    if (queueStore.previous) {
      return queueStore.previous
    }

    if (preferences.repeat_mode === 'REPEAT_ALL') {
      return queueStore.last
    }
  }

  public registerPlay (playable: Playable) {
    recentlyPlayedStore.add(playable)
    playableStore.registerPlay(playable)
    playable.play_count_registered = true
  }

  public preload (playable: Playable) {
    const audioElement = document.createElement('audio')
    audioElement.setAttribute('src', playableStore.getSourceUrl(playable))
    audioElement.setAttribute('preload', 'auto')
    audioElement.load()
    playable.preloaded = true
  }

  /**
   * Play a song. Because
   *
   * So many adventures couldn't happen today,
   * So many songs we forgot to play
   * So many dreams swinging out of the blue
   * We'll let them come true
   */
  public async play (playable: Playable, position = 0) {
    if (isEpisode(playable)) {
      useEpisodeProgressTracking().trackEpisode(playable)
    }

    queueStore.queueIfNotQueued(playable, 'after-current')

    // If for any reason (most likely a bug), the requested playable has been deleted, attempt the next item in the queue.
    if (isSong(playable) && playable.deleted) {
      logger.warn('Attempted to play a deleted playable', playable)

      if (this.next && this.next.id !== playable.id) {
        await this.playNext()
      }

      return
    }

    if (queueStore.current) {
      queueStore.current.playback_state = 'Stopped'
    }

    playable.playback_state = 'Playing'

    await this.setNowPlayingMeta(playable)

    // Manually set the `src` attribute of the audio to prevent plyr from resetting
    // the audio media object and cause our equalizer to malfunction.
    this.player.media.src = playableStore.getSourceUrl(playable)

    if (position === 0) {
      // We'll just "restart" playing the item, which will handle notification, scrobbling etc.
      // Fixes #898
      await this.restart()
    } else {
      this.seekTo(position)
      await this.resume()
    }

    this.setMediaSessionActionHandlers()
  }

  public showNotification (playable: Playable) {
    if (!isSong(playable) && !isEpisode(playable)) {
      throw new Error('Invalid playable type.')
    }

    if (preferences.show_now_playing_notification) {
      try {
        const notification = new window.Notification(`♫ ${playable.title}`, {
          icon: getPlayableProp(playable, 'album_cover', 'episode_image'),
          body: isSong(playable)
            ? `${playable.album_name} – ${playable.artist_name}`
            : playable.title,
        })

        notification.onclick = () => window.focus()

        window.setTimeout(() => notification.close(), 5000)
      } catch (error: unknown) {
        // Notification fails.
        // @link https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration/showNotification
        logger.error(error)
      }
    }

    if (!navigator.mediaSession) {
      return
    }

    navigator.mediaSession.metadata = new MediaMetadata({
      title: playable.title,
      artist: getPlayableProp(playable, 'artist_name', 'podcast_author'),
      album: getPlayableProp(playable, 'album_name', 'podcast_title'),
      artwork: [48, 64, 96, 128, 192, 256, 384, 512].map(d => ({
        src: getPlayableProp(playable, 'album_cover', 'episode_image'),
        sizes: `${d}x${d}`,
        type: 'image/png',
      })),
    })
  }

  public async restart () {
    const playable = queueStore.current!

    // Reset the "up next" value to let subscribers know that the next item is cleared
    // (because another playable, likely the "next" one, is being played)
    this.upNext.value = null

    this.recordStartTime(playable)
    socketService.broadcast('SOCKET_STREAMABLE', playable)

    try {
      http.silently.put('queue/playback-status', {
        song: playable.id,
        position: 0,
      })
    } catch (error: unknown) {
      logger.error(error)
    }

    this.player.restart()

    try {
      await this.player.media.play()
      navigator.mediaSession && (navigator.mediaSession.playbackState = 'playing')
      this.showNotification(playable)
    } catch (error: unknown) {
      // convert this into a warning, as an error will cause Cypress to fail the tests entirely
      logger.warn(error)
    }
  }

  public rotateRepeatMode () {
    let index = this.repeatModes.indexOf(preferences.repeat_mode) + 1

    if (index >= this.repeatModes.length) {
      index = 0
    }

    preferences.repeat_mode = this.repeatModes[index]
  }

  /**
   * Play the prev item the queue, if one is found.
   * If there's no prev item and the current mode is NO_REPEAT, we stop completely.
   */
  public async playPrev () {
    // If the item's duration is greater than 5 seconds, and we've passed 5 seconds into it,
    // restart playing instead.
    if (this.player.media.currentTime > 5 && queueStore.current!.length > 5) {
      this.player.restart()

      return
    }

    if (!this.previous && preferences.repeat_mode === 'NO_REPEAT') {
      await this.stop()
    } else {
      this.previous && await this.play(this.previous)
    }
  }

  /**
   * Play the next item in the queue if one is found.
   * If there's no next item and the current mode is NO_REPEAT, we stop completely.
   */
  public async playNext () {
    if (!this.next && preferences.repeat_mode === 'NO_REPEAT') {
      await this.stop() //  Nothing lasts forever, even cold November rain.
    } else {
      this.next && await this.play(this.next)
    }
  }

  public async stop () {
    if (this.player) {
      this.player.pause()
      this.seekTo(0)
    }

    document.title = 'Koel'

    queueStore.current && (queueStore.current.playback_state = 'Stopped')
    navigator.mediaSession && (navigator.mediaSession.playbackState = 'none')

    socketService.broadcast('SOCKET_PLAYBACK_STOPPED')
  }

  public async pause () {
    this.player.pause()

    queueStore.current!.playback_state = 'Paused'
    navigator.mediaSession && (navigator.mediaSession.playbackState = 'paused')

    socketService.broadcast('SOCKET_STREAMABLE', queueStore.current)
  }

  public async resume () {
    const playable = queueStore.current!

    if (!this.player.media.src) {
      // on first load when the queue is loaded from saved state, the player's src is empty
      // we need to properly set it as well as any kind of playback metadata
      this.player.media.src = playableStore.getSourceUrl(playable)
      this.seekTo(commonStore.state.queue_state.playback_position)

      await this.setNowPlayingMeta(queueStore.current!)
      this.recordStartTime(playable)
    }

    try {
      await this.player.media.play()
    } catch (error: unknown) {
      logger.error(error)
    }

    queueStore.current!.playback_state = 'Playing'
    navigator.mediaSession && (navigator.mediaSession.playbackState = 'playing')

    socketService.broadcast('SOCKET_STREAMABLE', playable)
  }

  public async toggle () {
    if (!queueStore.current) {
      await this.playFirstInQueue()
      return
    }

    if (queueStore.current.playback_state !== 'Playing') {
      await this.resume()
      return
    }

    this.pause()
  }

  /**
   * Queue up playables (replace them into the queue) and start playing right away.
   */
  public async queueAndPlay (playables: MaybeArray<Playable>, shuffled = false) {
    playables = arrayify(playables)

    if (shuffled) {
      playables = shuffle(playables)
    }

    await this.stop()
    queueStore.replaceQueueWith(playables)
    await this.play(queueStore.first)
  }

  public async playFirstInQueue () {
    queueStore.all.length && await this.play(queueStore.first)
  }

  private async setNowPlayingMeta (playable: Playable) {
    document.title = `${playable.title} ♫ Koel`
    this.player.media.setAttribute(
      'title',
      isSong(playable) ? `${playable.artist_name} - ${playable.title}` : playable.title,
    )

    if (isAudioContextSupported) {
      await audioService.context.resume()
    }
  }

  // Record the UNIX timestamp the playable starts playing, for scrobbling purpose
  private recordStartTime (song: Playable) {
    if (!isSong(song)) {
      return
    }

    song.play_start_time = Math.floor(Date.now() / 1000)
    song.play_count_registered = false
  }

  public forward (seconds: number): void {
    this.player.media.currentTime += seconds
  }

  protected onEnded (): void {
    if (
      queueStore.current
      && isSong(queueStore.current)
      && commonStore.state.uses_last_fm
      && userStore.current.preferences.lastfm_session_key
    ) {
      playableStore.scrobble(queueStore.current)
    }

    preferences.repeat_mode === 'REPEAT_ONE' ? this.restart() : this.playNext()
  }

  protected onError (error: ErrorEvent): void {
    logger.error(error)
    this.playNext()
  }

  protected onTimeUpdate (): void {
    const currentPlayable = queueStore.current

    if (!currentPlayable) {
      return
    }

    const media = this.player.media

    // If we've passed 25% of the playable, it's safe to say it has been "played".
    // See https://github.com/koel/koel/issues/1087
    if (!currentPlayable.play_count_registered && media.currentTime * 4 >= media.duration) {
      this.registerPlay(currentPlayable)
    }

    if (Math.ceil(media.currentTime) % 5 === 0) {
      // every 5 seconds, we save the current playback position to the server
      try {
        http.silently.put('queue/playback-status', {
          song: currentPlayable.id,
          position: Math.ceil(media.currentTime),
        })
      } catch (error: unknown) {
        logger.error(error)
      }

      // if the current item is an episode, we emit an event to update the progress on the client side as well
      if (isEpisode(currentPlayable)) {
        eventBus.emit('EPISODE_PROGRESS_UPDATED', currentPlayable, Math.ceil(media.currentTime))
      }
    }

    const nextPlayable = queueStore.next

    if (!nextPlayable) {
      return
    }

    // Set the "up next" value to the next playable if we're near the end of the current playback.
    this.upNext.value = media.currentTime + 15 > media.duration ? nextPlayable : null

    // Preload the next playable if we're near the end of the current playback.
    if (media.currentTime + PRELOAD_BUFFER > media.duration && !nextPlayable.preloaded) {
      this.preload(nextPlayable)
    }
  }

  public rewind (seconds: number): void {
    this.player.media.currentTime -= seconds
  }

  public fastSeek (position: number): void {
    this.player.media.fastSeek(position || 0)
  }

  public seekTo (position: number): void {
    this.player.seek(position || 0)
  }
}

export const playbackService = new QueuePlaybackService()
