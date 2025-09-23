import { nextTick, reactive } from 'vue'
import plyr from 'plyr'
import lodash from 'lodash'
import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { socketService } from '@/services/socketService'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { userStore } from '@/stores/userStore'
import { commonStore } from '@/stores/commonStore'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { logger } from '@/utils/logger'
import { playbackService } from '@/services/QueuePlaybackService'

describe('playbackService', () => {
  const h = createHarness({
    beforeEach: () => {
      h.createAudioPlayer()
      playbackService.activate(document.querySelector('.plyr')!)
    },
  })

  const setCurrentSong = (song?: Playable) => {
    song = reactive(song || h.factory('song', { playback_state: 'Playing' }))

    queueStore.state.playables = reactive([song])
    return song
  }

  it('only initializes once', () => {
    const spy = vi.spyOn(plyr, 'setup')

    playbackService.activate(document.querySelector('.plyr')!)
    expect(spy).not.toHaveBeenCalled()
  })

  it.each([
    [false, 100, 400, 1],
    [true, 100, 400, 0],
    [false, 100, 500, 0],
  ])(
    'when playCountRegistered is %s, current media time is %d, media duration is %d, then registerPlay() should be call %d times',
    (playCountRegistered, currentTime, duration, numberOfCalls) => {
      const song = h.factory('song', {
        play_count_registered: playCountRegistered,
        playback_state: 'Playing',
      })

      setCurrentSong(song)

      const mediaElement = playbackService.player.media

      // we can't set mediaElement.currentTime|duration directly because they're read-only
      h.setReadOnlyProperty(mediaElement, 'currentTime', currentTime)
      h.setReadOnlyProperty(mediaElement, 'duration', duration)

      const registerPlayMock = h.mock(playbackService, 'registerPlay')
      const putMock = h.mock(http, 'put')

      mediaElement.dispatchEvent(new Event('timeupdate'))

      expect(registerPlayMock).toHaveBeenCalledTimes(numberOfCalls)
      expect(putMock).toHaveBeenCalledWith('queue/playback-status', {
        song: song.id,
        position: currentTime,
      })
    },
  )

  it('plays next playable if current playable is errored', () => {
    const logMock = h.mock(logger, 'error')
    const playNextMock = h.mock(playbackService, 'playNext')

    const errorEvent = new Event('error')
    playbackService.player.media.dispatchEvent(errorEvent)

    expect(playNextMock).toHaveBeenCalled()
    expect(logMock).toHaveBeenCalledWith(errorEvent)
  })

  it('scrobbles if current playable ends', () => {
    commonStore.state.uses_last_fm = true
    userStore.state.current.preferences.lastfm_session_key = 'foo'

    const scrobbleMock = h.mock(playableStore, 'scrobble')
    playbackService.player.media.dispatchEvent(new Event('ended'))
    expect(scrobbleMock).toHaveBeenCalled()
  })

  it.each<[RepeatMode, number, number]>([['REPEAT_ONE', 1, 0], ['NO_REPEAT', 0, 1], ['REPEAT_ALL', 0, 1]])(
    'when playable ends, if repeat mode is %s then restart() is called %d times and playNext() is called %d times',
    (repeatMode, restartCalls, playNextCalls) => {
      setCurrentSong()

      const restartMock = h.mock(playbackService, 'restart')
      const playNextMock = h.mock(playbackService, 'playNext')

      commonStore.state.uses_last_fm = false // so that no scrobbling is made unnecessarily
      preferences.temporary.repeat_mode = repeatMode

      playbackService.player.media.dispatchEvent(new Event('ended'))

      expect(restartMock).toHaveBeenCalledTimes(restartCalls)
      expect(playNextMock).toHaveBeenCalledTimes(playNextCalls)
    },
  )

  it.each([
    [true, 300, 310, 0],
    [false, 300, 400, 0],
    [false, 300, 310, 1],
  ])(
    'when next playable preloaded is %s, current media time is %d, media duration is %d, then preload() should be called %d times',
    (preloaded, currentTime, duration, numberOfCalls) => {
      h.mock(playbackService, 'registerPlay')
      h.setReadOnlyProperty(queueStore, 'next', h.factory('song', { preloaded }))

      const mediaElement = playbackService.player.media

      h.setReadOnlyProperty(mediaElement, 'currentTime', currentTime)
      h.setReadOnlyProperty(mediaElement, 'duration', duration)

      const preloadMock = h.mock(playbackService, 'preload')
      h.mock(http, 'put')

      mediaElement.dispatchEvent(new Event('timeupdate'))

      expect(preloadMock).toHaveBeenCalledTimes(numberOfCalls)
    },
  )

  it('registers play', () => {
    const recentlyPlayedStoreAddMock = h.mock(recentlyPlayedStore, 'add')
    const registerPlayMock = h.mock(playableStore, 'registerPlay')
    const song = h.factory('song')

    playbackService.registerPlay(song)

    expect(recentlyPlayedStoreAddMock).toHaveBeenCalledWith(song)
    expect(registerPlayMock).toHaveBeenCalledWith(song)
    expect(song.play_count_registered).toBe(true)
  })

  it('preloads a playable', () => {
    const audioElement = {
      setAttribute: vi.fn(),
      load: vi.fn(),
    }

    const createElementMock = h.mock(document, 'createElement', audioElement)
    h.mock(playableStore, 'getSourceUrl').mockReturnValue('/foo?token=o5afd')
    const song = h.factory('song')

    playbackService.preload(song)

    expect(createElementMock).toHaveBeenCalledWith('audio')
    expect(audioElement.setAttribute).toHaveBeenNthCalledWith(1, 'src', '/foo?token=o5afd')
    expect(audioElement.setAttribute).toHaveBeenNthCalledWith(2, 'preload', 'auto')
    expect(audioElement.load).toHaveBeenCalled()
    expect(song.preloaded).toBe(true)
  })

  it('restarts a playable', async () => {
    const song = setCurrentSong()
    h.mock(Math, 'floor', 1000)
    const broadcastMock = h.mock(socketService, 'broadcast')
    const showNotificationMock = h.mock(playbackService, 'showNotification')
    const restartMock = h.mock(playbackService.player, 'restart')
    const putMock = h.mock(http, 'put')
    const playMock = h.mock(window.HTMLMediaElement.prototype, 'play')

    await playbackService.restart()

    expect(song.play_start_time).toEqual(1000)
    expect(song.play_count_registered).toBe(false)
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', song)
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
    ['REPEAT_ONE', 'NO_REPEAT'],
  ])('it switches from repeat mode %s to repeat mode %s', (fromMode, toMode) => {
    preferences.temporary.repeat_mode = fromMode
    playbackService.rotateRepeatMode()

    expect(preferences.repeat_mode).toEqual(toMode)
  })

  it('restarts playable if playPrev is triggered after 5 seconds', async () => {
    setCurrentSong()

    const mock = h.mock(playbackService.player, 'restart')
    h.setReadOnlyProperty(playbackService.player.media, 'currentTime', 6)

    await playbackService.playPrev()

    expect(mock).toHaveBeenCalled()
  })

  it('stops if playPrev is triggered when there is no prev playable and repeat mode is NO_REPEAT', async () => {
    const stopMock = h.mock(playbackService, 'stop')
    h.setReadOnlyProperty(playbackService.player.media, 'currentTime', 4)
    h.setReadOnlyProperty(playbackService, 'previous', undefined)
    preferences.temporary.repeat_mode = 'NO_REPEAT'

    await playbackService.playPrev()

    expect(stopMock).toHaveBeenCalled()
  })

  it('plays the previous playable', async () => {
    const previousSong = h.factory('song')
    h.setReadOnlyProperty(playbackService.player.media, 'currentTime', 4)
    h.setReadOnlyProperty(playbackService, 'previous', previousSong)
    const playMock = h.mock(playbackService, 'play')

    await playbackService.playPrev()

    expect(playMock).toHaveBeenCalledWith(previousSong)
  })

  it('stops if playNext is triggered when there is no next playable and repeat mode is NO_REPEAT', async () => {
    h.setReadOnlyProperty(playbackService, 'next', undefined)
    preferences.temporary.repeat_mode = 'NO_REPEAT'
    const stopMock = h.mock(playbackService, 'stop')

    await playbackService.playNext()

    expect(stopMock).toHaveBeenCalled()
  })

  it('plays the next playable', async () => {
    const nextSong = h.factory('song')
    h.setReadOnlyProperty(playbackService, 'next', nextSong)
    const playMock = h.mock(playbackService, 'play')

    await playbackService.playNext()

    expect(playMock).toHaveBeenCalledWith(nextSong)
  })

  it('stops playback', () => {
    const currentSong = setCurrentSong()
    const pauseMock = h.mock(playbackService.player, 'pause')
    const seekMock = h.mock(playbackService.player, 'seek')
    const broadcastMock = h.mock(socketService, 'broadcast')

    playbackService.stop()

    expect(currentSong.playback_state).toEqual('Stopped')
    expect(pauseMock).toHaveBeenCalled()
    expect(seekMock).toHaveBeenCalledWith(0)
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_PLAYBACK_STOPPED')
    expect(document.title).toEqual('Koel')
  })

  it('pauses playback', () => {
    const song = setCurrentSong()
    const pauseMock = h.mock(playbackService.player, 'pause')
    const broadcastMock = h.mock(socketService, 'broadcast')

    playbackService.pause()

    expect(song.playback_state).toEqual('Paused')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', song)
    expect(pauseMock).toHaveBeenCalled()
  })

  it('resumes playback', async () => {
    const song = setCurrentSong(h.factory('song', {
      playback_state: 'Paused',
    }))

    const playMock = h.mock(window.HTMLMediaElement.prototype, 'play')
    const broadcastMock = h.mock(socketService, 'broadcast')

    await playbackService.resume()

    expect(queueStore.current?.playback_state).toEqual('Playing')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', song)
    expect(playMock).toHaveBeenCalled()
  })

  it('plays first in queue if toggled when there is no current playable', async () => {
    queueStore.state.playables = []
    const playFirstInQueueMock = h.mock(playbackService, 'playFirstInQueue')

    await playbackService.toggle()

    expect(playFirstInQueueMock).toHaveBeenCalled()
  })

  it.each<[MethodOf<typeof playbackService>, PlaybackState]>([
    ['resume', 'Paused'],
    ['pause', 'Playing'],
  ])('%ss playback if toggled when current playable playback state is %s', async (action, playbackState) => {
    setCurrentSong(h.factory('song', { playback_state: playbackState }))
    const actionMock = h.mock(playbackService, action)
    await playbackService.toggle()

    expect(actionMock).toHaveBeenCalled()
  })

  it('queues and plays songs without shuffling', async () => {
    const songs = h.factory('song', 5)
    const replaceQueueMock = h.mock(queueStore, 'replaceQueueWith')
    const playMock = h.mock(playbackService, 'play')
    const firstSongInQueue = songs[0]
    const shuffleMock = h.mock(lodash, 'shuffle')
    h.setReadOnlyProperty(queueStore, 'first', firstSongInQueue)

    playbackService.queueAndPlay(songs)
    await nextTick()

    expect(shuffleMock).not.toHaveBeenCalled()
    expect(replaceQueueMock).toHaveBeenCalledWith(songs)
    expect(playMock).toHaveBeenCalledWith(firstSongInQueue)
  })

  it('queues and plays songs with shuffling', async () => {
    const songs = h.factory('song', 5)
    const shuffledSongs = h.factory('song', 5)
    const replaceQueueMock = h.mock(queueStore, 'replaceQueueWith')
    const playMock = h.mock(playbackService, 'play')
    const firstSongInQueue = songs[0]
    h.setReadOnlyProperty(queueStore, 'first', firstSongInQueue)
    const shuffleMock = h.mock(lodash, 'shuffle', shuffledSongs)

    playbackService.queueAndPlay(songs, true)
    await nextTick()

    expect(shuffleMock).toHaveBeenCalledWith(songs)
    expect(replaceQueueMock).toHaveBeenCalledWith(shuffledSongs)
    expect(playMock).toHaveBeenCalledWith(firstSongInQueue)
  })

  it('plays first playable in queue', async () => {
    const songs = h.factory('song', 5)
    queueStore.state.playables = songs
    h.setReadOnlyProperty(queueStore, 'first', songs[0])
    const playMock = h.mock(playbackService, 'play')

    await playbackService.playFirstInQueue()

    expect(playMock).toHaveBeenCalledWith(songs[0])
  })

  it('stops listening to media event after deactivation', () => {
    playbackService.deactivate()

    const logMock = h.mock(logger, 'error')
    const playNextMock = h.mock(playbackService, 'playNext')

    playbackService.player.media.dispatchEvent(new Event('error'))

    expect(playNextMock).not.toHaveBeenCalled()
    expect(logMock).not.toHaveBeenCalled()
  })
})
