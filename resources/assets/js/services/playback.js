import { shuffle, orderBy } from 'lodash'
import plyr from 'plyr'
import Vue from 'vue'

import { event } from '../utils'
import { queueStore, sharedStore, userStore, songStore, preferenceStore as preferences } from '../stores'
import config from '../config'
import router from '../router'

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

    /**
     * Listen to 'error' event on the audio player and play the next song if any.
     */
    document.querySelector('.plyr').addEventListener('error', e => {
      this.playNext()
    }, true)

    /**
     * Listen to 'ended' event on the audio player and play the next song in the queue.
     */
    document.querySelector('.plyr').addEventListener('ended', e => {
      if (sharedStore.state.useLastfm && userStore.current.preferences.lastfm_session_key) {
        songStore.scrobble(queueStore.current)
      }

      if (preferences.repeatMode === 'REPEAT_ONE') {
        this.restart()

        return
      }

      this.playNext()
    })

    /**
     * Attempt to preload the next song if the current song is about to end.
     */
    document.querySelector('.plyr').addEventListener('timeupdate', e => {
      if (!this.player.media.duration || this.player.media.currentTime + 10 < this.player.media.duration) {
        return
      }

      // The current song has only 10 seconds left to play.
      const nextSong = queueStore.next
      if (!nextSong || nextSong.preloaded) {
        return
      }

      const preloader = document.createElement('audio')
      preloader.setAttribute('src', songStore.getSourceUrl(nextSong))

      nextSong.preloaded = true
    })

    /**
     * Listen to 'input' event on the volume range control.
     * When user drags the volume control, this event will be triggered, and we
     * update the volume on the plyr object.
     */
    this.volumeInput.addEventListener('input', e => {
      this.setVolume(e.target.value)
    })

    // On init, set the volume to the value found in the local storage.
    this.setVolume(preferences.volume)

    // Init the equalizer if supported.
    event.emit('equalizer:init', this.player.media)

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

    // Add it into the "recently played" list
    songStore.addRecentlyPlayed(song)

    // Manually set the `src` attribute of the audio to prevent plyr from resetting
    // the audio media object and cause our equalizer to malfunction.
    this.player.media.src = songStore.getSourceUrl(song)

    document.title = `${song.title} ♫ ${config.appTitle}`
    document.querySelector('.plyr audio').setAttribute('title', `${song.artist.name} - ${song.title}`)

    // We'll just "restart" playing the song, which will handle notification, scrobbling etc.
    this.restart()
  },

  /**
   * Restart playing a song.
   */
  restart () {
    const song = queueStore.current

    // Record the UNIX timestamp the song start playing, for scrobbling purpose
    song.playStartTime = Math.floor(Date.now() / 1000)

    event.emit('song:played', song)

    this.player.restart()
    this.player.play()

    // Register the play to the server
    songStore.registerPlay(song)

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
  },

  /**
   * The next song in the queue.
   * If we're in REPEAT_ALL mode and there's no next song, just get the first song.
   *
   * @return {Object} The song
   */
  get next () {
    const next = queueStore.next

    if (next) {
      return next
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
    const prev = queueStore.previous

    if (prev) {
      return prev
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

    if (!prev && preferences.repeatMode === 'NO_REPEAT') {
      this.stop()

      return
    }

    this.play(prev)
  },

  /**
   * Play the next song in the queue, if one is found.
   * If the next song is not found and the current mode is NO_REPEAT, we stop completely.
   */
  playNext () {
    const next = this.next

    if (!next && preferences.repeatMode === 'NO_REPEAT') {
      //  Nothing lasts forever, even cold November rain.
      this.stop()

      return
    }

    this.play(next)
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
  },

  /**
   * Pause playback.
   */
  pause () {
    this.player.pause()
    queueStore.current.playbackState = 'paused'
  },

  /**
   * Resume playback.
   */
  resume () {
    this.player.play()
    queueStore.current.playbackState = 'playing'
    event.emit('song:played', queueStore.current)
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
    if (!queueStore.all.length) {
      this.queueAndPlay()

      return
    }

    this.play(queueStore.first)
  },

  /**
   * Play all songs by an artist.
   *
   * @param  {Object}     artist   The artist object
   * @param  {Boolean=true}   shuffled Whether to shuffle the songs
   */
  playAllByArtist (artist, shuffled = true) {
    if (!shuffled) {
      this.queueAndPlay(orderBy(artist.songs, 'album_id', 'track'))
    }

    this.queueAndPlay(artist.songs, true)
  },

  /**
   * Play all songs in an album.
   *
   * @param  {Object}     album  The album object
   * @param  {Boolean=true}   shuffled Whether to shuffle the songs
   */
  playAllInAlbum (album, shuffled = true) {
    if (!shuffled) {
      this.queueAndPlay(orderBy(album.songs, 'track'))
      return
    }

    this.queueAndPlay(album.songs, true)
  }
}
