import { nextTick, reactive } from 'vue'
import plyr from 'plyr'
import lodash from 'lodash'
import { expect, it, vi } from 'vitest'
import { eventBus, noop } from '@/utils'
import router from '@/router'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { socketService } from '@/services'
import { playbackService } from './playbackService'

import {
  commonStore,
  preferenceStore as preferences,
  queueStore,
  recentlyPlayedStore,
  songStore,
  userStore
} from '@/stores'

new class extends UnitTestCase {
  private setupEnvironment () {
    document.body.innerHTML = `
  <div class="plyr">
    <audio crossorigin="anonymous" controls/>
  </div>
  <input
    class="plyr__volume"
    id="volumeRange"
    max="10"
    step="0.1"
    title="Volume"
    type="range"
  >
  `

    window.AudioContext = vi.fn().mockImplementation(() => ({
      createMediaElementSource: vi.fn(noop)
    }))
  }

  protected beforeEach () {
    super.beforeEach(() => this.setupEnvironment())
  }

  private setCurrentSong (song?: Song) {
    song = reactive(song || factory<Song>('song', {
      playback_state: 'Playing'
    }))

    queueStore.state.songs = reactive([song])
    return song
  }

  protected test () {
    it('only initializes once', () => {
      const spy = vi.spyOn(plyr, 'setup')

      playbackService.init()
      expect(spy).toHaveBeenCalled()

      playbackService.init()
      expect(spy).toHaveBeenCalledTimes(1)
    })

    it.each<[boolean, boolean, number, number, number]>([
      [false, false, 100, 400, 1],
      [true, false, 100, 400, 0],
      [false, true, 100, 400, 0],
      [false, false, 100, 500, 0]
    ])(
      'when playCountRegistered is %s, isTranscoding is %s, current media time is %d, media duration is %d, then registerPlay() should be call %d times',
      (playCountRegistered, isTranscoding, currentTime, duration, numberOfCalls) => {
        this.setCurrentSong(factory<Song>('song', {
          play_count_registered: playCountRegistered,
          playback_state: 'Playing'
        }))

        this.setReadOnlyProperty(playbackService, 'isTranscoding', isTranscoding)
        playbackService.init()

        const mediaElement = playbackService.player.media

        // we can't set mediaElement.currentTime|duration directly because they're read-only
        this.setReadOnlyProperty(mediaElement, 'currentTime', currentTime)
        this.setReadOnlyProperty(mediaElement, 'duration', duration)

        const registerPlayMock = this.mock(playbackService, 'registerPlay')
        mediaElement.dispatchEvent(new Event('timeupdate'))

        expect(registerPlayMock).toHaveBeenCalledTimes(numberOfCalls)
      })

    it('plays next song if current song is errored', () => {
      playbackService.init()
      const playNextMock = this.mock(playbackService, 'playNext')
      playbackService.player!.media.dispatchEvent(new Event('error'))
      expect(playNextMock).toHaveBeenCalled()
    })

    it('scrobbles if current song ends', () => {
      commonStore.state.use_last_fm = true
      userStore.state.current = reactive(factory<User>('user', {
        preferences: {
          lastfm_session_key: 'foo'
        }
      }))

      playbackService.init()
      const scrobbleMock = this.mock(songStore, 'scrobble')
      playbackService.player!.media.dispatchEvent(new Event('ended'))
      expect(scrobbleMock).toHaveBeenCalled()
    })

    it.each<[RepeatMode, number, number]>([['REPEAT_ONE', 1, 0], ['NO_REPEAT', 0, 1], ['REPEAT_ALL', 0, 1]])(
      'when song ends, if repeat mode is %s then restart() is called %d times and playNext() is called %d times',
      (repeatMode, restartCalls, playNextCalls) => {
        commonStore.state.use_last_fm = false // so that no scrobbling is made unnecessarily
        preferences.repeatMode = repeatMode
        playbackService.init()
        const restartMock = this.mock(playbackService, 'restart')
        const playNextMock = this.mock(playbackService, 'playNext')

        playbackService.player!.media.dispatchEvent(new Event('ended'))

        expect(restartMock).toHaveBeenCalledTimes(restartCalls)
        expect(playNextMock).toHaveBeenCalledTimes(playNextCalls)
      })

    it.each([
      [false, true, 300, 310, 0],
      [true, false, 300, 310, 0],
      [false, false, 300, 400, 0],
      [false, false, 300, 310, 1]
    ])(
      'when next song preloaded is %s, isTranscoding is %s, current media time is %d, media duration is %d, then preload() should be called %d times',
      (preloaded, isTranscoding, currentTime, duration, numberOfCalls) => {
        this.setReadOnlyProperty(queueStore, 'next', factory<Song>('song', { preloaded }))
        this.setReadOnlyProperty(playbackService, 'isTranscoding', isTranscoding)
        playbackService.init()

        const mediaElement = playbackService.player!.media

        this.setReadOnlyProperty(mediaElement, 'currentTime', currentTime)
        this.setReadOnlyProperty(mediaElement, 'duration', duration)

        const preloadMock = this.mock(playbackService, 'preload')
        mediaElement.dispatchEvent(new Event('timeupdate'))

        expect(preloadMock).toHaveBeenCalledTimes(numberOfCalls)
      }
    )

    it('registers play', () => {
      const recentlyPlayedStoreAddMock = this.mock(recentlyPlayedStore, 'add')
      const registerPlayMock = this.mock(songStore, 'registerPlay')
      const song = factory<Song>('song')

      playbackService.registerPlay(song)

      expect(recentlyPlayedStoreAddMock).toHaveBeenCalledWith(song)
      expect(registerPlayMock).toHaveBeenCalledWith(song)
      expect(song.play_count_registered).toBe(true)
    })

    it('preloads a song', () => {
      const audioElement = {
        setAttribute: vi.fn(),
        load: vi.fn()
      }

      const createElementMock = this.mock(document, 'createElement', audioElement)
      this.mock(songStore, 'getSourceUrl').mockReturnValue('/foo?token=o5afd')
      const song = factory<Song>('song')

      playbackService.preload(song)

      expect(createElementMock).toHaveBeenCalledWith('audio')
      expect(audioElement.setAttribute).toHaveBeenNthCalledWith(1, 'src', '/foo?token=o5afd')
      expect(audioElement.setAttribute).toHaveBeenNthCalledWith(2, 'preload', 'auto')
      expect(audioElement.load).toHaveBeenCalled()
      expect(song.preloaded).toBe(true)
    })

    it('restarts a song', async () => {
      const song = this.setCurrentSong()
      this.mock(Math, 'floor', 1000)
      const emitMock = this.mock(eventBus, 'emit')
      const broadcastMock = this.mock(socketService, 'broadcast')
      const showNotificationMock = this.mock(playbackService, 'showNotification')
      const restartMock = this.mock(playbackService.player!, 'restart')
      const playMock = this.mock(window.HTMLMediaElement.prototype, 'play')

      await playbackService.restart()

      expect(song.play_start_time).toEqual(1000)
      expect(song.play_count_registered).toBe(false)
      expect(emitMock).toHaveBeenCalledWith('SONG_STARTED', song)
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_SONG', song)
      expect(showNotificationMock).toHaveBeenCalled()
      expect(restartMock).toHaveBeenCalled()
      expect(playMock).toHaveBeenCalled()
    })

    it.each<[RepeatMode, RepeatMode]>([
      ['NO_REPEAT', 'REPEAT_ALL'],
      ['REPEAT_ALL', 'REPEAT_ONE'],
      ['REPEAT_ONE', 'NO_REPEAT']
    ])('it switches from repeat mode %s to repeat mode %s', (fromMode, toMode) => {
      preferences.repeatMode = fromMode
      playbackService.changeRepeatMode()

      expect(preferences.repeatMode).toEqual(toMode)
    })

    it('restarts song if playPrev is triggered after 5 seconds', async () => {
      const mock = this.mock(playbackService.player!, 'restart')
      this.setReadOnlyProperty(playbackService.player!.media, 'currentTime', 6)

      await playbackService.playPrev()

      expect(mock).toHaveBeenCalled()
    })

    it('stops if playPrev is triggered when there is no prev song and repeat mode is NO_REPEAT', async () => {
      const stopMock = this.mock(playbackService, 'stop')
      this.setReadOnlyProperty(playbackService.player!.media, 'currentTime', 4)
      this.setReadOnlyProperty(playbackService, 'previous', undefined)
      preferences.repeatMode = 'NO_REPEAT'

      await playbackService.playPrev()

      expect(stopMock).toHaveBeenCalled()
    })

    it('plays the previous song', async () => {
      const previousSong = factory('song')
      this.setReadOnlyProperty(playbackService.player!.media, 'currentTime', 4)
      this.setReadOnlyProperty(playbackService, 'previous', previousSong)
      const playMock = this.mock(playbackService, 'play')

      await playbackService.playPrev()

      expect(playMock).toHaveBeenCalledWith(previousSong)
    })

    it('stops if playNext is triggered when there is no next song and repeat mode is NO_REPEAT', async () => {
      this.setReadOnlyProperty(playbackService, 'next', undefined)
      preferences.repeatMode = 'NO_REPEAT'
      const stopMock = this.mock(playbackService, 'stop')

      await playbackService.playNext()

      expect(stopMock).toHaveBeenCalled()
    })

    it('plays the next song', async () => {
      const nextSong = factory('song')
      this.setReadOnlyProperty(playbackService, 'next', nextSong)
      const playMock = this.mock(playbackService, 'play')

      await playbackService.playNext()

      expect(playMock).toHaveBeenCalledWith(nextSong)
    })

    it('stops playback', () => {
      const currentSong = factory<Song>('song')
      const pauseMock = this.mock(playbackService.player!, 'pause')
      const seekMock = this.mock(playbackService.player!, 'seek')
      const broadcastMock = this.mock(socketService, 'broadcast')

      playbackService.stop()

      expect(currentSong.playback_state).toEqual('Stopped')
      expect(pauseMock).toHaveBeenCalled()
      expect(seekMock).toHaveBeenCalledWith(0)
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_PLAYBACK_STOPPED')
      expect(document.title).toEqual('Koel')
    })

    it('pauses playback', () => {
      const song = this.setCurrentSong()
      const pauseMock = this.mock(playbackService.player!, 'pause')
      const broadcastMock = this.mock(socketService, 'broadcast')

      playbackService.pause()

      expect(song.playback_state).toEqual('Paused')
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_SONG', song)
      expect(pauseMock).toHaveBeenCalled()
    })

    it('resumes playback', async () => {
      const song = this.setCurrentSong(factory<Song>('song', {
        playback_state: 'Paused'
      }))

      const playMock = this.mock(window.HTMLMediaElement.prototype, 'play')
      const broadcastMock = this.mock(socketService, 'broadcast')
      const emitMock = this.mock(eventBus, 'emit')

      playbackService.init()
      await playbackService.resume()

      expect(queueStore.current?.playback_state).toEqual('Playing')
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_SONG', song)
      expect(playMock).toHaveBeenCalled()
      expect(emitMock).toHaveBeenCalledWith('SONG_STARTED', song)
    })

    it('plays first in queue if toggled when there is no current song', async () => {
      queueStore.clear()
      const playFirstInQueueMock = this.mock(playbackService, 'playFirstInQueue')

      await playbackService.toggle()

      expect(playFirstInQueueMock).toHaveBeenCalled()
    })

    it.each<[MethodOf<typeof playbackService>, PlaybackState]>([
      ['resume', 'Paused'],
      ['pause', 'Playing']
    ])('%ss playback if toggled when current song playback state is %s', async (action, playbackState) => {
      this.setCurrentSong(factory<Song>('song', { playback_state: playbackState }))
      const actionMock = this.mock(playbackService, action)
      await playbackService.toggle()

      expect(actionMock).toHaveBeenCalled()
    })

    it('queues and plays songs without shuffling', async () => {
      const songs = factory<Song>('song', 5)
      const replaceQueueMock = this.mock(queueStore, 'replaceQueueWith')
      const goMock = this.mock(router, 'go')
      const playMock = this.mock(playbackService, 'play')
      const firstSongInQueue = songs[0]
      const shuffleMock = this.mock(lodash, 'shuffle')
      this.setReadOnlyProperty(queueStore, 'first', firstSongInQueue)

      await playbackService.queueAndPlay(songs)
      await nextTick()

      expect(shuffleMock).not.toHaveBeenCalled()
      expect(replaceQueueMock).toHaveBeenCalledWith(songs)
      expect(goMock).toHaveBeenCalledWith('queue')
      expect(playMock).toHaveBeenCalledWith(firstSongInQueue)
    })

    it('queues and plays songs with shuffling', async () => {
      const songs = factory<Song>('song', 5)
      const shuffledSongs = factory<Song>('song', 5)
      const replaceQueueMock = this.mock(queueStore, 'replaceQueueWith')
      const goMock = this.mock(router, 'go')
      const playMock = this.mock(playbackService, 'play')
      const firstSongInQueue = songs[0]
      this.setReadOnlyProperty(queueStore, 'first', firstSongInQueue)
      const shuffleMock = this.mock(lodash, 'shuffle', shuffledSongs)

      await playbackService.queueAndPlay(songs, true)
      await nextTick()

      expect(shuffleMock).toHaveBeenCalledWith(songs)
      expect(replaceQueueMock).toHaveBeenCalledWith(shuffledSongs)
      expect(goMock).toHaveBeenCalledWith('queue')
      expect(playMock).toHaveBeenCalledWith(firstSongInQueue)
    })

    it('plays first song in queue', async () => {
      const songs = factory<Song>('song', 5)
      queueStore.all = songs
      this.setReadOnlyProperty(queueStore, 'first', songs[0])
      const playMock = this.mock(playbackService, 'play')

      await playbackService.playFirstInQueue()

      expect(playMock).toHaveBeenCalledWith(songs[0])
    })
  }
}
