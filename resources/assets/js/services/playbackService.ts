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

import { arrayify, isAudioContextSupported, logger } from '@/utils'
import { audioService, socketService, volumeManager } from '@/services'

/**
 * The number of seconds before the current song ends to start preload the next one.
 */
const PRELOAD_BUFFER = 30

class PlaybackService {
  public player!: Plyr
  private repeatModes: RepeatMode[] = ['NO_REPEAT', 'REPEAT_ALL', 'REPEAT_ONE']
  private initialized = false

  public init (plyrWrapper: HTMLElement) {
    if (this.initialized) return

    this.player = plyr.setup(plyrWrapper, { controls: [] })[0]

    this.listenToMediaEvents(this.player.media)
    this.setMediaSessionActionHandlers()

    watch(volumeManager.volume, volume => this.player.setVolume(volume), { immediate: true })

    this.initialized = true
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
    // If for any reason (most likely a bug), the requested song has been deleted, just attempt the next song.
    if (song.deleted) {
      logger.warn('Attempted to play a deleted song', song)

      if (this.next && this.next.id !== song.id) {
        await this.playNext()
      }

      return
    }

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
      await audioService.context.resume()
    }

    await this.restart()
  }

  public showNotification (song: Song) {
    if (preferences.notify) {
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
    }

    if (!navigator.mediaSession) return

    navigator.mediaSession.metadata = new MediaMetadata({
      title: song.title,
      artist: song.artist_name,
      album: song.album_name,
      artwork: [48, 64, 96, 128, 192, 256, 384, 512].map(d => ({
        src: song.album_cover,
        sizes: `${d}x${d}`,
        type: 'image/png'
      }))
    })
  }

  public async restart () {
    const song = queueStore.current!

    // Record the UNIX timestamp the song starts playing, for scrobbling purpose
    song.play_start_time = Math.floor(Date.now() / 1000)
    song.play_count_registered = false

    socketService.broadcast('SOCKET_SONG', song)

    this.player.restart()

    try {
      await this.player.media.play()
      navigator.mediaSession && (navigator.mediaSession.playbackState = 'playing')
      this.showNotification(song)
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
    // If the song's duration is greater than 5 seconds, and we've passed 5 seconds into it,
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
    try {
      await this.player.media.play()
    } catch (error) {
      logger.error(error)
    }

    queueStore.current!.playback_state = 'Playing'
    navigator.mediaSession && (navigator.mediaSession.playbackState = 'playing')

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
    await this.play(queueStore.first)
  }

  public async playFirstInQueue () {
    queueStore.all.length && await this.play(queueStore.first)
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
        if (!media.duration || media.currentTime * 4 >= media.duration) {
          this.registerPlay(currentSong)
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
