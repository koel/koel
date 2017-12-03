import { shuffle, orderBy } from 'lodash'
import plyr from 'plyr'
import Vue from 'vue'
import isMobile from 'ismobilejs'

import { event, isMediaSessionSupported } from '@/utils'
import { queueStore, sharedStore, userStore, songStore, preferenceStore as preferences } from '@/stores'
import { socket } from '@/services'
import config from '@/config'
import router from '@/router'

export const playback = {
  player: null,
  volumeInput: null,
  repeatModes: ['NO_REPEAT', 'REPEAT_ALL', 'REPEAT_ONE'],
  initialized: false,

  /**
   * Initialize the playback service for this whole Koel app.
   */
  init () {
    // We don't need to init this service twice, or the media events will be duplicated.
    if (this.initialized) {
      return
    }

    this.player = plyr.setup({
      controls: []
    })[0]

    this.audio = document.querySelector('audio')
    this.volumeInput = document.getElementById('volumeRange')

    const player = document.querySelector('.plyr')

    /**
     * Listen to 'error' event on the audio player and play the next song if any.
     */
    player.addEventListener('error', () => this.playNext(), true)

    /**
     * Listen to 'ended' event on the audio player and play the next song in the queue.
     */
    player.addEventListener('ended', e => {
      if (sharedStore.state.useLastfm && userStore.current.preferences.lastfm_session_key) {
        songStore.scrobble(queueStore.current)
      }

      preferences.repeatMode === 'REPEAT_ONE' ? this.restart() : this.playNext()
    })

    /**
     * Attempt to preload the next song.
     */
    player.addEventListener('canplaythrough', e => {
      const nextSong = queueStore.next
      if (!nextSong || nextSong.preloaded || (isMobile.any && preferences.transcodeOnMobile)) {
        // Don't preload if
        // - there's no next song
        // - next song has already been preloaded
        // - we're on mobile and "transcode" option is checked
        return
      }

      const audio = document.createElement('audio')
      audio.setAttribute('src', songStore.getSourceUrl(nextSong))
      audio.setAttribute('preload', 'auto')
      audio.load()
      nextSong.preloaded = true
    })

    player.addEventListener('timeupdate', e => {
      const song = queueStore.current

      if (this.player.media.currentTime > 10 && !song.registeredPlayCount) {
        // After 10 seconds, register a play count and add it into "recently played" list
        songStore.addRecentlyPlayed(song)
        songStore.registerPlay(song)

        song.registeredPlayCount = true
      }
    })

    // On init, set the volume to the value found in the local storage.
    this.setVolume(preferences.volume)

    // Init the equalizer if supported.
    event.emit('equalizer:init', this.player.media)

    if (isMediaSessionSupported()) {
      navigator.mediaSession.setActionHandler('play', () => this.resume())
      navigator.mediaSession.setActionHandler('pause', () => this.pause())
      navigator.mediaSession.setActionHandler('previoustrack', () => this.playPrev())
      navigator.mediaSession.setActionHandler('nexttrack', () => this.playNext())
    }

    socket.listen('playback:toggle', () => this.toggle())
      .listen('playback:next', () => this.playNext())
      .listen('playback:prev', () => this.playPrev())
      .listen('status:get', () => {
        const data = queueStore.current ? songStore.generateDataToBroadcast(queueStore.current) : {}
        data.volume = this.volumeInput.value
        socket.broadcast('status', data)
      })
      .listen('song:getcurrent', () => {
        socket.broadcast(
          'song',
          queueStore.current
            ? songStore.generateDataToBroadcast(queueStore.current)
            : { song: null }
        )
      })
      .listen('volume:set', ({ volume }) => this.setVolume(volume))

    this.initialized = true
  },

  /**
   * Play a song. Because
   *
   * So many adventures couldn't happen today,
   * So many songs we forgot to play
   * So many dreams swinging out of the blue
   * We'll let them come true
   *
   * @param  {Object} song The song to play
   */
  play (song) {
    if (!song) {
      return
    }

    if (queueStore.current) {
      queueStore.current.playbackState = 'stopped'
    }

    song.playbackState = 'playing'

    // Set the song as the current song
    queueStore.current = song

    // Manually set the `src` attribute of the audio to prevent plyr from resetting
    // the audio media object and cause our equalizer to malfunction.
    this.player.media.src = songStore.getSourceUrl(song)

    document.title = `${song.title} ♫ ${config.appTitle}`
    document.querySelector('.plyr audio').setAttribute('title', `${song.artist.name} - ${song.title}`)

    // We'll just "restart" playing the song, which will handle notification, scrobbling etc.
    this.restart()
  },

  /**
   * Show the "now playing" notification for a song.
   *
   * @param  {Object} song
   */
  showNotification (song) {
    // Show the notification if we're allowed to
    if (!window.Notification || !preferences.notify) {
      return
    }

    try {
      const notif = new window.Notification(`♫ ${song.title}`, {
        icon: song.album.cover,
        body: `${song.album.name} – ${song.artist.name}`
      })

      notif.onclick = () => window.focus()

      // Close the notif after 5 secs.
      window.setTimeout(() => notif.close(), 5000)
    } catch (e) {
      // Notification fails.
      // @link https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration/showNotification
    }

    if ('mediaSession' in navigator) {
      /* global MediaMetadata */
      navigator.mediaSession.metadata = new MediaMetadata({
        title: song.title,
        artist: song.artist.name,
        album: song.album.name,
        artwork: [
          { src: song.album.cover, sizes: '256x256', type: 'image/png' }
        ]
      })
    }
  },

  /**
   * Restart playing a song.
   */
  restart () {
    const song = queueStore.current

    this.showNotification(song)

    // Record the UNIX timestamp the song start playing, for scrobbling purpose
    song.playStartTime = Math.floor(Date.now() / 1000)

    song.registeredPlayCount = false

    event.emit('song:played', song)

    socket.broadcast('song', songStore.generateDataToBroadcast(song))

    this.player.restart()
    this.player.play()
  },

  /**
   * The next song in the queue.
   * If we're in REPEAT_ALL mode and there's no next song, just get the first song.
   *
   * @return {Object} The song
   */
  get next () {
    if (queueStore.next) {
      return queueStore.next
    }

    if (preferences.repeatMode === 'REPEAT_ALL') {
      return queueStore.first
    }
  },

  /**
   * The previous song in the queue.
   * If we're in REPEAT_ALL mode and there's no prev song, get the last song.
   *
   * @return {Object} The song
   */
  get previous () {
    if (queueStore.previous) {
      return queueStore.previous
    }

    if (preferences.repeatMode === 'REPEAT_ALL') {
      return queueStore.last
    }
  },

  /**
   * Circle through the repeat mode.
   * The selected mode will be stored into local storage as well.
   */
  changeRepeatMode () {
    let index = this.repeatModes.indexOf(preferences.repeatMode) + 1

    if (index >= this.repeatModes.length) {
      index = 0
    }

    preferences.repeatMode = this.repeatModes[index]
  },

  /**
   * Play the prev song in the queue, if one is found.
   * If the prev song is not found and the current mode is NO_REPEAT, we stop completely.
   */
  playPrev () {
    // If the song's duration is greater than 5 seconds and we've passed 5 seconds into it,
    // restart playing instead.
    if (this.player.media.currentTime > 5 && queueStore.current.length > 5) {
      this.player.restart()

      return
    }

    const prev = this.previous
    !prev && preferences.repeatMode === 'NO_REPEAT'
      ? this.stop()
      : this.play(prev)
  },

  /**
   * Play the next song in the queue, if one is found.
   * If the next song is not found and the current mode is NO_REPEAT, we stop completely.
   */
  playNext () {
    const next = this.next
    !next && preferences.repeatMode === 'NO_REPEAT'
      ? this.stop() //  Nothing lasts forever, even cold November rain.
      : this.play(next)
  },

  /**
   * Set the volume level.
   *
   * @param {Number}     volume   0-10
   * @param {Boolean=true}   persist  Whether the volume should be saved into local storage
   */
  setVolume (volume, persist = true) {
    this.player.setVolume(volume)

    if (persist) {
      preferences.volume = volume
    }

    this.volumeInput.value = volume
  },

  /**
   * Mute playback.
   */
  mute () {
    this.setVolume(0, false)
  },

  /**
   * Unmute playback.
   */
  unmute () {
    // If the saved volume is 0, we unmute to the default level (7).
    if (preferences.volume === '0' || preferences.volume === 0) {
      preferences.volume = 7
    }

    this.setVolume(preferences.volume)
  },

  /**
   * Completely stop playback.
   */
  stop () {
    document.title = config.appTitle
    this.player.pause()
    this.player.seek(0)

    if (queueStore.current) {
      queueStore.current.playbackState = 'stopped'
    }

    socket.broadcast('playback:stopped')
  },

  /**
   * Pause playback.
   */
  pause () {
    this.player.pause()
    queueStore.current.playbackState = 'paused'
    socket.broadcast('song', songStore.generateDataToBroadcast(queueStore.current))
  },

  /**
   * Resume playback.
   */
  resume () {
    this.player.play()
    queueStore.current.playbackState = 'playing'
    event.emit('song:played', queueStore.current)
    socket.broadcast('song', songStore.generateDataToBroadcast(queueStore.current))
  },

  /**
   * Toggle playback.
   */
  toggle () {
    if (!queueStore.current) {
      this.playFirstInQueue()
      return
    }

    if (queueStore.current.playbackState !== 'playing') {
      this.resume()
      return
    }

    this.pause()
  },

  /**
   * Queue up songs (replace them into the queue) and start playing right away.
   *
   * @param {?Array.<Object>} songs  An array of song objects. Defaults to all songs if null.
   * @param {Boolean=false}   shuffled Whether to shuffle the songs before playing.
   */
  queueAndPlay (songs = null, shuffled = false) {
    if (!songs) {
      songs = songStore.all
    }

    if (!songs.length) {
      return
    }

    if (shuffled) {
      songs = shuffle(songs)
    }

    queueStore.queue(songs, true)

    // Wrap this inside a nextTick() to wait for the DOM to complete updating
    // and then play the first song in the queue.
    Vue.nextTick(() => {
      router.go('queue')
      this.play(queueStore.first)
    })
  },

  /**
   * Play the first song in the queue.
   * If the current queue is empty, try creating it by shuffling all songs.
   */
  playFirstInQueue () {
    queueStore.all.length ? this.play(queueStore.first) : this.queueAndPlay()
  },

  /**
   * Play all songs by an artist.
   *
   * @param  {Object}     artist   The artist object
   * @param  {Boolean=true}   shuffled Whether to shuffle the songs
   */
  playAllByArtist ({ songs }, shuffled = true) {
    shuffled
      ? this.queueAndPlay(songs, true)
      : this.queueAndPlay(orderBy(songs, ['album_id', 'disc', 'track']))
  },

  /**
   * Play all songs in an album.
   *
   * @param  {Object}     album  The album object
   * @param  {Boolean=true}   shuffled Whether to shuffle the songs
   */
  playAllInAlbum ({ songs }, shuffled = true) {
    shuffled
      ? this.queueAndPlay(songs, true)
      : this.queueAndPlay(orderBy(songs, ['disc', 'track']))
  }
}
