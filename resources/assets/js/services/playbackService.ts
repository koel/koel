import isMobile from 'ismobilejs'
import plyr from 'plyr'
import { watch } from 'vue'
import { shuffle, throttle } from 'lodash'

import {
  commonStore,
  preferenceStore as preferences,
  queueStore,
  recentlyPlayedStore,
  songStore,
  userStore
} from '@/stores'

import { arrayify, eventBus, getPlayableProp, isAudioContextSupported, isEpisode, isSong, logger } from '@/utils'
import { audioService, http, socketService, volumeManager } from '@/services'
import { useEpisodeProgressTracking } from '@/composables'

/**
 * The number of seconds before the current song ends to start preload the next one.
 */
const PRELOAD_BUFFER = 30

class PlaybackService {
  public player!: Plyr
  private repeatModes: RepeatMode[] = ['NO_REPEAT', 'REPEAT_ALL', 'REPEAT_ONE']
  private initialized = false

  public get isTranscoding () {
    return isMobile.any && preferences.transcode_on_mobile
  }

  /**
   * The next song in the queue.
   * If we're in REPEAT_ALL mode and there's no next song, just get the first song.
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
   * The previous song in the queue.
   * If we're in REPEAT_ALL mode and there's no prev song, get the last song.
   */
  public get previous () {
    if (queueStore.previous) {
      return queueStore.previous
    }

    if (preferences.repeat_mode === 'REPEAT_ALL') {
      return queueStore.last
    }
  }

  public init (plyrWrapper: HTMLElement) {
    if (this.initialized) return

    this.player = plyr.setup(plyrWrapper, { controls: [] })[0]

    this.listenToMediaEvents(this.player.media)
    this.setMediaSessionActionHandlers()

    watch(volumeManager.volume, volume => this.player.setVolume(volume), { immediate: true })

    this.initialized = true
  }

  public registerPlay (playable: Playable) {
    recentlyPlayedStore.add(playable)
    songStore.registerPlay(playable)
    playable.play_count_registered = true
  }

  public preload (playable: Playable) {
    const audioElement = document.createElement('audio')
    audioElement.setAttribute('src', songStore.getSourceUrl(playable))
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

    queueStore.queueIfNotQueued(playable)

    // If for any reason (most likely a bug), the requested song has been deleted, just attempt the next song.
    if (isSong(playable) && playable.deleted) {
      logger.warn('Attempted to play a deleted song', playable)

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
    this.player.media.src = songStore.getSourceUrl(playable)

    if (position === 0) {
      // We'll just "restart" playing the song, which will handle notification, scrobbling etc.
      // Fixes #898
      await this.restart()
    } else {
      this.player.seek(position)
      await this.resume()
    }

    this.setMediaSessionActionHandlers()
  }

  public showNotification (playable: Playable) {
    if (!isSong(playable) && !isEpisode(playable)) {
      throw 'Invalid playable type.'
    }

    if (preferences.show_now_playing_notification) {
      try {
        const notification = new window.Notification(`♫ ${playable.title}`, {
          icon: getPlayableProp(playable, 'album_cover', 'episode_image'),
          body: isSong(playable)
            ? `${playable.album_name} – ${playable.artist_name}`
            : playable.title
        })

        notification.onclick = () => window.focus()

        window.setTimeout(() => notification.close(), 5000)
      } catch (error: unknown) {
        // Notification fails.
        // @link https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration/showNotification
        logger.error(error)
      }
    }

    if (!navigator.mediaSession) return

    navigator.mediaSession.metadata = new MediaMetadata({
      title: playable.title,
      artist: getPlayableProp(playable, 'artist_name', 'podcast_author'),
      album: getPlayableProp(playable, 'album_name', 'podcast_title'),
      artwork: [48, 64, 96, 128, 192, 256, 384, 512].map(d => ({
        src: getPlayableProp(playable, 'album_cover', 'episode_image'),
        sizes: `${d}x${d}`,
        type: 'image/png'
      }))
    })
  }

  public async restart () {
    const playable = queueStore.current!

    this.recordStartTime(playable)
    this.broadcastSong(playable)

    try {
      http.silently.put('queue/playback-status', {
        song: playable.id,
        position: 0
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
   * Play the prev song in the queue, if one is found.
   * If the prev song is not found and the current mode is NO_REPEAT, we stop completely.
   */
  public async playPrev () {
    // If the song's duration is greater than 5 seconds, and we've passed 5 seconds into it,
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
   * Play the next song in the queue, if one is found.
   * If the next song is not found and the current mode is NO_REPEAT, we stop completely.
   */
  public async playNext () {
    if (!this.next && preferences.repeat_mode === 'NO_REPEAT') {
      await this.stop() //  Nothing lasts forever, even cold November rain.
    } else {
      this.next && await this.play(this.next)
    }
  }

  public async stop () {
    document.title = 'Koel'
    this.player.pause()
    this.player.seek(0)

    queueStore.current && (queueStore.current.playback_state = 'Stopped')
    navigator.mediaSession && (navigator.mediaSession.playbackState = 'none')

    socketService.broadcast('SOCKET_PLAYBACK_STOPPED')
  }

  public pause () {
    this.player.pause()

    queueStore.current!.playback_state = 'Paused'
    navigator.mediaSession && (navigator.mediaSession.playbackState = 'paused')

    socketService.broadcast('SOCKET_SONG', queueStore.current)
  }

  public async resume () {
    const song = queueStore.current!

    if (!this.player.media.src) {
      // on first load when the queue is loaded from saved state, the player's src is empty
      // we need to properly set it as well as any kind of playback metadata
      this.player.media.src = songStore.getSourceUrl(song)
      this.player.seek(commonStore.state.queue_state.playback_position);

      await this.setNowPlayingMeta(queueStore.current!)
      this.recordStartTime(song)
    }

    try {
      await this.player.media.play()
    } catch (error: unknown) {
      logger.error(error)
    }

    queueStore.current!.playback_state = 'Playing'
    navigator.mediaSession && (navigator.mediaSession.playbackState = 'playing')

    this.broadcastSong(song)
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

  public seekBy (seconds: number) {
    if (this.player.media.duration) {
      this.player.media.currentTime += seconds
    }
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
      isSong(playable) ? `${playable.artist_name} - ${playable.title}` : playable.title
    )

    if (isAudioContextSupported) {
      await audioService.context.resume()
    }
  }

  // Record the UNIX timestamp the song starts playing, for scrobbling purpose
  private recordStartTime (song: Playable) {
    if (!isSong(song)) return

    song.play_start_time = Math.floor(Date.now() / 1000)
    song.play_count_registered = false
  }

  private broadcastSong (playable: Playable) {
    socketService.broadcast('SOCKET_SONG', playable)
  }

  private setMediaSessionActionHandlers () {
    if (!navigator.mediaSession) return

    navigator.mediaSession.setActionHandler('play', () => this.resume())
    navigator.mediaSession.setActionHandler('pause', () => this.pause())
    navigator.mediaSession.setActionHandler('stop', () => this.stop())
    navigator.mediaSession.setActionHandler('previoustrack', () => this.playPrev())
    navigator.mediaSession.setActionHandler('nexttrack', () => this.playNext())

    navigator.mediaSession.setActionHandler('seekbackward', details => {
      this.player.media.currentTime -= (details.seekOffset || 10)
    })

    navigator.mediaSession.setActionHandler('seekforward', details => {
      this.player.media.currentTime += (details.seekOffset || 10)
    })

    navigator.mediaSession.setActionHandler('seekto', details => {
      if (details.fastSeek && 'fastSeek' in this.player.media) {
        this.player.media.fastSeek(details.seekTime || 0)
        return
      }

      this.player.media.currentTime = details.seekTime || 0
    })
  }

  private listenToMediaEvents (media: HTMLMediaElement) {
    media.addEventListener('error', () => this.playNext(), true)

    media.addEventListener('ended', () => {
      if (
        isSong(queueStore.current!)
        && commonStore.state.uses_last_fm
        && userStore.current.preferences!.lastfm_session_key
      ) {
        songStore.scrobble(queueStore.current!)
      }

      preferences.repeat_mode === 'REPEAT_ONE' ? this.restart() : this.playNext()
    })

    let timeUpdateHandler = () => {
      const currentSong = queueStore.current

      if (!currentSong) return

      if (!currentSong.play_count_registered && !this.isTranscoding) {
        // if we've passed 25% of the song, it's safe to say the song has been "played".
        // Refer to https://github.com/koel/koel/issues/1087
        if (!media.duration || media.currentTime * 4 >= media.duration) {
          this.registerPlay(currentSong)
        }
      }

      if (Math.ceil(media.currentTime) % 5 === 0) {
        // every 5 seconds, we save the current playback position to the server
        try {
          http.silently.put('queue/playback-status', {
            song: currentSong.id,
            position: Math.ceil(media.currentTime)
          })
        } catch (error: unknown) {
          logger.error(error)
        }

        // if the current song is an episode, we emit an event to update the progress on the client side as well
        if (isEpisode(currentSong)) {
          eventBus.emit('EPISODE_PROGRESS_UPDATED', currentSong, Math.ceil(media.currentTime))
        }
      }

      const nextSong = queueStore.next

      if (!nextSong || nextSong.preloaded || this.isTranscoding) {
        return
      }

      if (media.duration && media.currentTime + PRELOAD_BUFFER > media.duration) {
        this.preload(nextSong)
      }
    }

    if (process.env.NODE_ENV !== 'test') {
      timeUpdateHandler = throttle(timeUpdateHandler, 1000)
    }

    media.addEventListener('timeupdate', timeUpdateHandler)
  }
}

export const playbackService = new PlaybackService()
