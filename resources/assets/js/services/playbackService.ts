import isMobile from 'ismobilejs'
import plyr from 'plyr'
import { shuffle, throttle } from 'lodash'
import { nextTick } from 'vue'

import {
  commonStore,
  preferenceStore as preferences,
  queueStore,
  recentlyPlayedStore,
  songStore,
  userStore
} from '@/stores'

import { arrayify, eventBus, isAudioContextSupported, logger } from '@/utils'
import { audioService, socketService } from '@/services'
import router from '@/router'

/**
 * The number of seconds before the current song ends to start preload the next one.
 */
const PRELOAD_BUFFER = 30
const DEFAULT_VOLUME_VALUE = 7
const VOLUME_INPUT_SELECTOR = '#volumeInput'

class PlaybackService {
  public player: Plyr
  private volumeInput: HTMLInputElement
  private repeatModes: RepeatMode[] = ['NO_REPEAT', 'REPEAT_ALL', 'REPEAT_ONE']
  private initialized = false

  public init () {
    if (this.initialized) {
      return
    }

    this.initialized = true

    this.player = plyr.setup('.plyr', {
      controls: []
    })[0]

    this.volumeInput = document.querySelector<HTMLInputElement>(VOLUME_INPUT_SELECTOR)!
    this.listenToMediaEvents(this.player.media)

    if (isAudioContextSupported) {
      try {
        this.setVolume(preferences.volume)
      } catch (e) {
      }

      audioService.init(this.player.media)
      eventBus.emit('INIT_EQUALIZER')
    }

    this.setMediaSessionActionHandlers()
  }

  private setMediaSessionActionHandlers () {
    if (!navigator.mediaSession) {
      return
    }

    navigator.mediaSession.setActionHandler('play', () => this.resume())
    navigator.mediaSession.setActionHandler('pause', () => this.pause())
    navigator.mediaSession.setActionHandler('previoustrack', () => this.playPrev())
    navigator.mediaSession.setActionHandler('nexttrack', () => this.playNext())
  }

  private listenToMediaEvents (mediaElement: HTMLMediaElement) {
    mediaElement.addEventListener('error', () => this.playNext(), true)

    mediaElement.addEventListener('ended', () => {
      if (commonStore.state.use_last_fm && userStore.current.preferences!.lastfm_session_key) {
        songStore.scrobble(queueStore.current!)
      }

      preferences.repeatMode === 'REPEAT_ONE' ? this.restart() : this.playNext()
    })

    let timeUpdateHandler = () => {
      const currentSong = queueStore.current

      if (!currentSong) return

      if (!currentSong.play_count_registered && !this.isTranscoding) {
        // if we've passed 25% of the song, it's safe to say the song has been "played".
        // Refer to https://github.com/koel/koel/issues/1087
        if (!mediaElement.duration || mediaElement.currentTime * 4 >= mediaElement.duration) {
          this.registerPlay(currentSong)
        }
      }

      const nextSong = queueStore.next

      if (!nextSong || nextSong.preloaded || this.isTranscoding) {
        return
      }

      if (mediaElement.duration && mediaElement.currentTime + PRELOAD_BUFFER > mediaElement.duration) {
        this.preload(nextSong)
      }
    }

    if (process.env.NODE_ENV !== 'test') {
      timeUpdateHandler = throttle(timeUpdateHandler, 1000)
    }

    mediaElement.addEventListener('timeupdate', timeUpdateHandler)
  }

  public registerPlay (song: Song) {
    recentlyPlayedStore.add(song)
    songStore.registerPlay(song)
    song.play_count_registered = true
  }

  public preload (song: Song) {
    const audioElement = document.createElement('audio')
    audioElement.setAttribute('src', songStore.getSourceUrl(song))
    audioElement.setAttribute('preload', 'auto')
    audioElement.load()
    song.preloaded = true
  }

  /**
   * Play a song. Because
   *
   * So many adventures couldn't happen today,
   * So many songs we forgot to play
   * So many dreams swinging out of the blue
   * We'll let them come true
   */
  public async play (song: Song) {
    document.title = `${song.title} ♫ Koel`
    this.player.media.setAttribute('title', `${song.artist_name} - ${song.title}`)

    if (queueStore.current) {
      queueStore.current.playback_state = 'Stopped'
    }

    song.playback_state = 'Playing'

    // Manually set the `src` attribute of the audio to prevent plyr from resetting
    // the audio media object and cause our equalizer to malfunction.
    this.player.media.src = songStore.getSourceUrl(song)

    // We'll just "restart" playing the song, which will handle notification, scrobbling etc.
    // Fixes #898
    if (isAudioContextSupported) {
      await audioService.getContext().resume()
    }

    await this.restart()
  }

  public showNotification (song: Song) {
    if (!window.Notification || !preferences.notify) {
      return
    }

    try {
      const notification = new window.Notification(`♫ ${song.title}`, {
        icon: song.album_cover,
        body: `${song.album_name} – ${song.artist_name}`
      })

      notification.onclick = () => window.focus()

      window.setTimeout(() => notification.close(), 5000)
    } catch (e) {
      // Notification fails.
      // @link https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration/showNotification
      logger.error(e)
    }

    navigator.mediaSession.metadata = new MediaMetadata({
      title: song.title,
      artist: song.artist_name,
      album: song.album_name,
      artwork: [
        {
          src: song.album_cover,
          sizes: '256x256',
          type: 'image/png'
        }
      ]
    })
  }

  public async restart () {
    const song = queueStore.current!

    this.showNotification(song)

    // Record the UNIX timestamp the song starts playing, for scrobbling purpose
    song.play_start_time = Math.floor(Date.now() / 1000)
    song.play_count_registered = false

    eventBus.emit('SONG_STARTED', song)
    socketService.broadcast('SOCKET_SONG', song)

    this.player.restart()

    try {
      await this.player.media.play()
    } catch (error) {
      // convert this into a warning, as an error will cause Cypress to fail the tests entirely
      logger.warn(error)
    }
  }

  public get isTranscoding () {
    return isMobile.any && preferences.transcodeOnMobile
  }

  /**
   * The next song in the queue.
   * If we're in REPEAT_ALL mode and there's no next song, just get the first song.
   */
  public get next () {
    if (queueStore.next) {
      return queueStore.next
    }

    if (preferences.repeatMode === 'REPEAT_ALL') {
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

    if (preferences.repeatMode === 'REPEAT_ALL') {
      return queueStore.last
    }
  }

  /**
   * Circle through the repeat mode.
   * The selected mode will be stored into local storage as well.
   */
  public changeRepeatMode () {
    let index = this.repeatModes.indexOf(preferences.repeatMode) + 1

    if (index >= this.repeatModes.length) {
      index = 0
    }

    preferences.repeatMode = this.repeatModes[index]
  }

  /**
   * Play the prev song in the queue, if one is found.
   * If the prev song is not found and the current mode is NO_REPEAT, we stop completely.
   */
  public async playPrev () {
    // If the song's duration is greater than 5 seconds and we've passed 5 seconds into it,
    // restart playing instead.
    if (this.player.media.currentTime > 5 && queueStore.current!.length > 5) {
      this.player.restart()

      return
    }

    if (!this.previous && preferences.repeatMode === 'NO_REPEAT') {
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
    if (!this.next && preferences.repeatMode === 'NO_REPEAT') {
      await this.stop() //  Nothing lasts forever, even cold November rain.
    } else {
      this.next && await this.play(this.next)
    }
  }

  public getVolume () {
    return preferences.volume
  }

  /**
   * @param {Number}     volume   0-10
   * @param {Boolean=true}   persist  Whether the volume should be saved into local storage
   */
  public setVolume (volume: number, persist = true) {
    this.player.setVolume(volume)
    persist && (preferences.volume = volume)
    this.volumeInput.value = String(volume)
  }

  public mute () {
    this.setVolume(0, false)
  }

  public unmute () {
    preferences.volume = preferences.volume || DEFAULT_VOLUME_VALUE
    this.setVolume(preferences.volume)
  }

  public async stop () {
    document.title = 'Koel'
    this.player.pause()
    this.player.seek(0)

    if (queueStore.current) {
      queueStore.current.playback_state = 'Stopped'
    }

    socketService.broadcast('SOCKET_PLAYBACK_STOPPED')
  }

  public pause () {
    this.player.pause()
    queueStore.current!.playback_state = 'Paused'
    socketService.broadcast('SOCKET_SONG', queueStore.current)
  }

  public async resume () {
    try {
      await this.player.media.play()
    } catch (error) {
      logger.error(error)
    }

    queueStore.current!.playback_state = 'Playing'
    eventBus.emit('SONG_STARTED', queueStore.current)
    socketService.broadcast('SOCKET_SONG', queueStore.current)
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
   * Queue up songs (replace them into the queue) and start playing right away.
   *
   * @param {?Song[]} songs  An array of song objects. Defaults to all songs if null.
   * @param {Boolean=false}   shuffled Whether to shuffle the songs before playing.
   */
  public async queueAndPlay (songs: Song | Song[], shuffled = false) {
    songs = arrayify(songs)

    if (shuffled) {
      songs = shuffle(songs)
    }

    await this.stop()
    queueStore.replaceQueueWith(songs)

    // Wait for the DOM to complete updating and play the first song in the queue.
    await nextTick()
    router.go('queue')
    await this.play(queueStore.first)
  }

  public async playFirstInQueue () {
    queueStore.all.length && await this.play(queueStore.first)
  }
}

export const playbackService = new PlaybackService()
