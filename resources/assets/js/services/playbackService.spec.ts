import { nextTick, reactive } from 'vue'
import plyr from 'plyr'
import lodash from 'lodash'
import { expect, it, vi } from 'vitest'
import { noop } from '@/utils'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { http, socketService } from '@/services'
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
  protected beforeEach () {
    super.beforeEach(() => this.setupEnvironment())
  }

  protected test () {
    it('only initializes once', () => {
      const spy = vi.spyOn(plyr, 'setup')

      playbackService.init(document.querySelector('.plyr')!)
      expect(spy).toHaveBeenCalled()

      playbackService.init(document.querySelector('.plyr')!)
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
        const song = factory('song', {
          play_count_registered: playCountRegistered,
          playback_state: 'Playing'
        })

        this.setCurrentSong(song)

        this.setReadOnlyProperty(playbackService, 'isTranscoding', isTranscoding)
        playbackService.init(document.querySelector('.plyr')!)

        const mediaElement = playbackService.player.media

        // we can't set mediaElement.currentTime|duration directly because they're read-only
        this.setReadOnlyProperty(mediaElement, 'currentTime', currentTime)
        this.setReadOnlyProperty(mediaElement, 'duration', duration)

        const registerPlayMock = this.mock(playbackService, 'registerPlay')
        const putMock = this.mock(http, 'put')

        mediaElement.dispatchEvent(new Event('timeupdate'))

        expect(registerPlayMock).toHaveBeenCalledTimes(numberOfCalls)
        expect(putMock).toHaveBeenCalledWith('queue/playback-status', {
          song: song.id,
          position: currentTime
        })
      })

    it('plays next song if current song is errored', () => {
      playbackService.init(document.querySelector('.plyr')!)
      const playNextMock = this.mock(playbackService, 'playNext')
      playbackService.player!.media.dispatchEvent(new Event('error'))
      expect(playNextMock).toHaveBeenCalled()
    })

    it('scrobbles if current song ends', () => {
      commonStore.state.uses_last_fm = true
      userStore.state.current = reactive(factory('user', {
        preferences: {
          lastfm_session_key: 'foo'
        }
      }))

      playbackService.init(document.querySelector('.plyr')!)
      const scrobbleMock = this.mock(songStore, 'scrobble')
      playbackService.player!.media.dispatchEvent(new Event('ended'))
      expect(scrobbleMock).toHaveBeenCalled()
    })

    it.each<[RepeatMode, number, number]>([['REPEAT_ONE', 1, 0], ['NO_REPEAT', 0, 1], ['REPEAT_ALL', 0, 1]])(
      'when song ends, if repeat mode is %s then restart() is called %d times and playNext() is called %d times',
      (repeatMode, restartCalls, playNextCalls) => {
        commonStore.state.uses_last_fm = false // so that no scrobbling is made unnecessarily
        preferences.repeat_mode = repeatMode
        playbackService.init(document.querySelector('.plyr')!)
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
        this.mock(playbackService, 'registerPlay')

        this.setReadOnlyProperty(queueStore, 'next', factory('song', { preloaded }))
        this.setReadOnlyProperty(playbackService, 'isTranscoding', isTranscoding)
        playbackService.init(document.querySelector('.plyr')!)

        const mediaElement = playbackService.player!.media

        this.setReadOnlyProperty(mediaElement, 'currentTime', currentTime)
        this.setReadOnlyProperty(mediaElement, 'duration', duration)

        const preloadMock = this.mock(playbackService, 'preload')
        this.mock(http, 'put')

        mediaElement.dispatchEvent(new Event('timeupdate'))

        expect(preloadMock).toHaveBeenCalledTimes(numberOfCalls)
      }
    )

    it('registers play', () => {
      const recentlyPlayedStoreAddMock = this.mock(recentlyPlayedStore, 'add')
      const registerPlayMock = this.mock(songStore, 'registerPlay')
      const song = factory('song')

      playbackService.registerPlay(song)

      expect(recentlyPlayedStoreAddMock).toHaveBeenCalledWith(song)
      expect(registerPlayMock).toHaveBeenCalledWith(song)
      expect(song.play_count_registered).toBe(true)
    })

    it('preloads a song', () => {
      playbackService.init(document.querySelector('.plyr')!)

      const audioElement = {
        setAttribute: vi.fn(),
        load: vi.fn()
      }

      const createElementMock = this.mock(document, 'createElement', audioElement)
      this.mock(songStore, 'getSourceUrl').mockReturnValue('/foo?token=o5afd')
      const song = factory('song')

      playbackService.preload(song)

      expect(createElementMock).toHaveBeenCalledWith('audio')
      expect(audioElement.setAttribute).toHaveBeenNthCalledWith(1, 'src', '/foo?token=o5afd')
      expect(audioElement.setAttribute).toHaveBeenNthCalledWith(2, 'preload', 'auto')
      expect(audioElement.load).toHaveBeenCalled()
      expect(song.preloaded).toBe(true)
    })

    it('restarts a song', async () => {
      playbackService.init(document.querySelector('.plyr')!)

      const song = this.setCurrentSong()
      this.mock(Math, 'floor', 1000)
      const broadcastMock = this.mock(socketService, 'broadcast')
      const showNotificationMock = this.mock(playbackService, 'showNotification')
      const restartMock = this.mock(playbackService.player!, 'restart')
      const putMock = this.mock(http, 'put')
      const playMock = this.mock(window.HTMLMediaElement.prototype, 'play')

      await playbackService.restart()

      expect(song.play_start_time).toEqual(1000)
      expect(song.play_count_registered).toBe(false)
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_SONG', song)
      expect(showNotificationMock).toHaveBeenCalled()
      expect(restartMock).toHaveBeenCalled()
      expect(playMock).toHaveBeenCalled()

      expect(putMock).toHaveBeenCalledWith('queue/playback-status', {
        song: song.id,
        position: 0,
      })
    })

    it.each<[RepeatMode, RepeatMode]>([
      ['NO_REPEAT', 'REPEAT_ALL'],
      ['REPEAT_ALL', 'REPEAT_ONE'],
      ['REPEAT_ONE', 'NO_REPEAT']
    ])('it switches from repeat mode %s to repeat mode %s', (fromMode, toMode) => {
      playbackService.init(document.querySelector('.plyr')!)
      preferences.repeat_mode = fromMode
      playbackService.rotateRepeatMode()

      expect(preferences.repeat_mode).toEqual(toMode)
    })

    it('restarts song if playPrev is triggered after 5 seconds', async () => {
      this.setCurrentSong()
      playbackService.init(document.querySelector('.plyr')!)

      const mock = this.mock(playbackService.player!, 'restart')
      this.setReadOnlyProperty(playbackService.player!.media, 'currentTime', 6)

      await playbackService.playPrev()

      expect(mock).toHaveBeenCalled()
    })

    it('stops if playPrev is triggered when there is no prev song and repeat mode is NO_REPEAT', async () => {
      playbackService.init(document.querySelector('.plyr')!)

      const stopMock = this.mock(playbackService, 'stop')
      this.setReadOnlyProperty(playbackService.player!.media, 'currentTime', 4)
      this.setReadOnlyProperty(playbackService, 'previous', undefined)
      preferences.repeat_mode = 'NO_REPEAT'

      await playbackService.playPrev()

      expect(stopMock).toHaveBeenCalled()
    })

    it('plays the previous song', async () => {
      playbackService.init(document.querySelector('.plyr')!)

      const previousSong = factory('song')
      this.setReadOnlyProperty(playbackService.player!.media, 'currentTime', 4)
      this.setReadOnlyProperty(playbackService, 'previous', previousSong)
      const playMock = this.mock(playbackService, 'play')

      await playbackService.playPrev()

      expect(playMock).toHaveBeenCalledWith(previousSong)
    })

    it('stops if playNext is triggered when there is no next song and repeat mode is NO_REPEAT', async () => {
      playbackService.init(document.querySelector('.plyr')!)

      this.setReadOnlyProperty(playbackService, 'next', undefined)
      preferences.repeat_mode = 'NO_REPEAT'
      const stopMock = this.mock(playbackService, 'stop')

      await playbackService.playNext()

      expect(stopMock).toHaveBeenCalled()
    })

    it('plays the next song', async () => {
      playbackService.init(document.querySelector('.plyr')!)

      const nextSong = factory('song')
      this.setReadOnlyProperty(playbackService, 'next', nextSong)
      const playMock = this.mock(playbackService, 'play')

      await playbackService.playNext()

      expect(playMock).toHaveBeenCalledWith(nextSong)
    })

    it('stops playback', () => {
      playbackService.init(document.querySelector('.plyr')!)

      const currentSong = this.setCurrentSong()
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
      playbackService.init(document.querySelector('.plyr')!)

      const song = this.setCurrentSong()
      const pauseMock = this.mock(playbackService.player!, 'pause')
      const broadcastMock = this.mock(socketService, 'broadcast')

      playbackService.pause()

      expect(song.playback_state).toEqual('Paused')
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_SONG', song)
      expect(pauseMock).toHaveBeenCalled()
    })

    it('resumes playback', async () => {
      const song = this.setCurrentSong(factory('song', {
        playback_state: 'Paused'
      }))

      const playMock = this.mock(window.HTMLMediaElement.prototype, 'play')
      const broadcastMock = this.mock(socketService, 'broadcast')

      playbackService.init(document.querySelector('.plyr')!)
      await playbackService.resume()

      expect(queueStore.current?.playback_state).toEqual('Playing')
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_SONG', song)
      expect(playMock).toHaveBeenCalled()
    })

    it('plays first in queue if toggled when there is no current song', async () => {
      playbackService.init(document.querySelector('.plyr')!)
      queueStore.state.playables = []
      const playFirstInQueueMock = this.mock(playbackService, 'playFirstInQueue')

      await playbackService.toggle()

      expect(playFirstInQueueMock).toHaveBeenCalled()
    })

    it.each<[MethodOf<typeof playbackService>, PlaybackState]>([
      ['resume', 'Paused'],
      ['pause', 'Playing']
    ])('%ss playback if toggled when current song playback state is %s', async (action, playbackState) => {
      playbackService.init(document.querySelector('.plyr')!)

      this.setCurrentSong(factory('song', { playback_state: playbackState }))
      const actionMock = this.mock(playbackService, action)
      await playbackService.toggle()

      expect(actionMock).toHaveBeenCalled()
    })

    it('queues and plays songs without shuffling', async () => {
      playbackService.init(document.querySelector('.plyr')!)

      const songs = factory('song', 5)
      const replaceQueueMock = this.mock(queueStore, 'replaceQueueWith')
      const playMock = this.mock(playbackService, 'play')
      const firstSongInQueue = songs[0]
      const shuffleMock = this.mock(lodash, 'shuffle')
      this.setReadOnlyProperty(queueStore, 'first', firstSongInQueue)

      playbackService.queueAndPlay(songs)
      await nextTick()

      expect(shuffleMock).not.toHaveBeenCalled()
      expect(replaceQueueMock).toHaveBeenCalledWith(songs)
      expect(playMock).toHaveBeenCalledWith(firstSongInQueue)
    })

    it('queues and plays songs with shuffling', async () => {
      playbackService.init(document.querySelector('.plyr')!)

      const songs = factory('song', 5)
      const shuffledSongs = factory('song', 5)
      const replaceQueueMock = this.mock(queueStore, 'replaceQueueWith')
      const playMock = this.mock(playbackService, 'play')
      const firstSongInQueue = songs[0]
      this.setReadOnlyProperty(queueStore, 'first', firstSongInQueue)
      const shuffleMock = this.mock(lodash, 'shuffle', shuffledSongs)

      playbackService.queueAndPlay(songs, true)
      await nextTick()

      expect(shuffleMock).toHaveBeenCalledWith(songs)
      expect(replaceQueueMock).toHaveBeenCalledWith(shuffledSongs)
      expect(playMock).toHaveBeenCalledWith(firstSongInQueue)
    })

    it('plays first song in queue', async () => {
      playbackService.init(document.querySelector('.plyr')!)

      const songs = factory('song', 5)
      queueStore.state.playables = songs
      this.setReadOnlyProperty(queueStore, 'first', songs[0])
      const playMock = this.mock(playbackService, 'play')

      await playbackService.playFirstInQueue()

      expect(playMock).toHaveBeenCalledWith(songs[0])
    })
  }

  private setupEnvironment () {
    document.body.innerHTML = `
  <div class="plyr">
    <audio crossorigin="anonymous" controls/>
  </div>
  `

    window.AudioContext = vi.fn().mockImplementation(() => ({
      createMediaElementSource: vi.fn(noop)
    }))
  }

  private setCurrentSong (song?: Song) {
    song = reactive(song || factory('song', {
      playback_state: 'Playing'
    }))

    queueStore.state.playables = reactive([song])
    return song
  }
}
