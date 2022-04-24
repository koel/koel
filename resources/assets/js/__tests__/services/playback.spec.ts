import plyr from 'plyr'
import { orderBy, shuffle } from 'lodash'
import { playbackService, socketService } from '@/services'
import { eventBus, noop } from '@/utils'
import { mock } from '@/__tests__/__helpers__'
import {
  commonStore,
  preferenceStore as preferences,
  queueStore,
  recentlyPlayedStore,
  songStore,
  userStore
} from '@/stores'
import router from '@/router'
import factory from '@/__tests__/factory'
import Vue from 'vue'
import FunctionPropertyNames = jest.FunctionPropertyNames

const prepareForTests = () => {
  document.body.innerHTML = `
  <div class="plyr">
    <audio crossorigin="anonymous" controls></audio>
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
  window.AudioContext = jest.fn().mockImplementation(() => {
    return {
      createMediaElementSource: jest.fn(noop)
    }
  })
}

describe('services/playback', () => {
  beforeEach(() => prepareForTests())

  afterEach(() => {
    jest.resetModules()
    jest.restoreAllMocks()
    jest.clearAllMocks()
  })

  it('only initializes once', () => {
    const plyrSetupSpy = jest.spyOn(plyr, 'setup')
    playbackService.init()
    expect(plyrSetupSpy).toHaveBeenCalled()
    playbackService.init()
    expect(plyrSetupSpy).toHaveBeenCalledTimes(1)
  })

  describe('listens to media events', () => {
    it.each<[boolean, boolean, number, number, number]>([
      /* playCountRegistered, isTranscoding, current media time, media duration, number of registerPlay()'s calls */
      [false, false, 100, 400, 1],
      [true, false, 100, 400, 0],
      [false, true, 100, 400, 0],
      [false, false, 100, 500, 0]
    ])(
      'when playCountRegistered is %s, isTranscoding is %s, current media time is %d, media duration is %d, then registerPlay() should be call %d times',
      (playCountRegistered, isTranscoding, currentTime, duration, numberOfCalls) => {
        queueStore.current = factory<Song>('song', { playCountRegistered })
        Object.defineProperty(playbackService, 'isTranscoding', { get: () => isTranscoding })
        playbackService.init()
        const mediaElement = playbackService.player!.media
        // we can't set mediaElement.currentTime|duration directly because they're read-only
        Object.defineProperties(mediaElement, {
          currentTime: {
            value: currentTime,
            configurable: true
          },
          duration: {
            value: duration,
            configurable: true
          }
        })

        const registerPlayMock = mock(playbackService, 'registerPlay')
        mediaElement.dispatchEvent(new Event('timeupdate'))
        expect(registerPlayMock).toHaveBeenCalledTimes(numberOfCalls)
      })

    it('plays next song if current song is errored', () => {
      playbackService.init()
      const playNextMock = mock(playbackService, 'playNext')
      playbackService.player!.media.dispatchEvent(new Event('error'))
      expect(playNextMock).toHaveBeenCalled()
    })

    it('scrobbles if current song ends', () => {
      queueStore.current = factory<Song>('song')
      commonStore.state.useLastfm = true
      userStore.current = factory<User>('user', {
        preferences: {
          lastfm_session_key: 'foo'
        }
      })

      playbackService.init()
      const scrobbleMock = mock(songStore, 'scrobble')
      playbackService.player!.media.dispatchEvent(new Event('ended'))
      expect(scrobbleMock).toHaveBeenCalled()
    })

    it.each<[RepeatMode, number, number]>([['REPEAT_ONE', 1, 0], ['NO_REPEAT', 0, 1], ['REPEAT_ALL', 0, 1]])(
      'when song ends, if repeat mode is %s then restart() is called %d times and playNext() is called %d times',
      (repeatMode, restartCalls, playNextCalls) => {
        commonStore.state.useLastfm = false // so that no scrobbling is made unnecessarily
        preferences.repeatMode = repeatMode
        playbackService.init()
        const restartMock = mock(playbackService, 'restart')
        const playNextMock = mock(playbackService, 'playNext')
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
      'when next song preloaded is %s, isTrancoding is %s, current media time is %d, media duration is %d, then preload() should be called %d times',
      (preloaded, isTranscoding, currentTime, duration, numberOfCalls) => {
        queueStore.current = factory<Song>('song', { playCountRegistered: true }) // avoid triggering play count logic
        Object.defineProperty(queueStore, 'next', {
          get: () => factory('song', { preloaded })
        })
        Object.defineProperty(playbackService, 'isTranscoding', { get: () => isTranscoding })
        playbackService.init()
        const mediaElement = playbackService.player!.media
        Object.defineProperties(mediaElement, {
          currentTime: {
            value: currentTime,
            configurable: true
          },
          duration: {
            value: duration,
            configurable: true
          }
        })

        const preloadMock = mock(playbackService, 'preload')
        mediaElement.dispatchEvent(new Event('timeupdate'))
        expect(preloadMock).toHaveBeenCalledTimes(numberOfCalls)
      }
    )
  })

  it('registers play', () => {
    const recentlyPlayedStoreAddMock = mock(recentlyPlayedStore, 'add')
    const recentlyPlayedStoreFetchAllMock = mock(recentlyPlayedStore, 'fetchAll')
    const registerPlayMock = mock(songStore, 'registerPlay')
    const song = factory<Song>('song')
    playbackService.registerPlay(song)
    expect(recentlyPlayedStoreAddMock).toHaveBeenCalledWith(song)
    expect(recentlyPlayedStoreFetchAllMock).toHaveBeenCalled()
    expect(registerPlayMock).toHaveBeenCalledWith(song)
    expect(song.playCountRegistered).toBe(true)
  })

  it('preloads a song', () => {
    const setAttributeMock = jest.fn()
    const loadMock = jest.fn()

    const audioElement = {
      setAttribute: setAttributeMock,
      load: loadMock
    }

    const createElementMock = mock(document, 'createElement', audioElement)
    mock(songStore, 'getSourceUrl').mockReturnValue('/foo?token=o5afd')
    const song = factory<Song>('song')
    playbackService.preload(song)
    expect(createElementMock).toHaveBeenCalledWith('audio')
    expect(setAttributeMock).toHaveBeenNthCalledWith(1, 'src', '/foo?token=o5afd')
    expect(setAttributeMock).toHaveBeenNthCalledWith(2, 'preload', 'auto')
    expect(loadMock).toHaveBeenCalled()
    expect(song.preloaded).toBe(true)
  })

  it('restarts a song', async () => {
    const song = factory<Song>('song')
    Object.defineProperty(queueStore, 'current', {
      get: () => song
    })
    mock(Math, 'floor', 1000)
    const emitMock = mock(eventBus, 'emit')
    const broadcastMock = mock(socketService, 'broadcast')
    const showNotificationMock = mock(playbackService, 'showNotification')
    const dataToBroadcast = {}
    mock(songStore, 'generateDataToBroadcast', dataToBroadcast)
    const restartMock = mock(playbackService.player!, 'restart')
    const playMock = mock(window.HTMLMediaElement.prototype, 'play')

    await playbackService.restart()
    expect(song.playStartTime).toEqual(1000)
    expect(song.playCountRegistered).toBe(false)
    expect(emitMock).toHaveBeenCalledWith('SONG_STARTED', song)
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_SONG', dataToBroadcast)
    expect(showNotificationMock).toHaveBeenCalled()
    expect(restartMock).toHaveBeenCalled()
    expect(playMock).toHaveBeenCalled()
  })

  it.each<[RepeatMode, RepeatMode]>([
    ['NO_REPEAT', 'REPEAT_ALL'],
    ['REPEAT_ALL', 'REPEAT_ONE'],
    ['REPEAT_ONE', 'NO_REPEAT']
  ])(
    'it switches from repeat mode %s to repeat mode %s',
    (fromMode, toMode) => {
      preferences.repeatMode = fromMode

      playbackService.changeRepeatMode()
      expect(preferences.repeatMode).toEqual(toMode)
    })

  it('restarts song if playPrev is triggered after 5 seconds', async () => {
    const restartMock = mock(playbackService.player!, 'restart')
    Object.defineProperty(playbackService.player!.media, 'currentTime', {
      get: () => 6
    })
    Object.defineProperty(queueStore, 'current', {
      get: () => factory('song', { length: 120 })
    })

    await playbackService.playPrev()
    expect(restartMock).toHaveBeenCalled()
  })

  it('stops if playPrev is triggered when there is no prev song and repeat mode is NO_REPEAT', async () => {
    const stopMock = mock(playbackService, 'stop')
    Object.defineProperty(playbackService.player!.media, 'currentTime', {
      get: () => 4
    })
    Object.defineProperty(queueStore, 'current', {
      get: () => factory('song', { length: 120 })
    })
    Object.defineProperty(playbackService, 'previous', {
      get: () => null
    })
    preferences.repeatMode = 'NO_REPEAT'

    await playbackService.playPrev()
    expect(stopMock).toHaveBeenCalled()
  })

  it('plays the previous song', async () => {
    const previousSong = factory('song')
    Object.defineProperty(playbackService, 'previous', {
      get: () => previousSong
    })
    Object.defineProperty(playbackService.player!.media, 'currentTime', {
      get: () => 4
    })
    Object.defineProperty(queueStore, 'current', {
      get: () => factory('song', { length: 120 })
    })
    const playMock = mock(playbackService, 'play')

    await playbackService.playPrev()
    expect(playMock).toHaveBeenCalledWith(previousSong)
  })

  it('stops if playNext is triggered when there is no next song and repeat mode is NO_REPEAT', async () => {
    Object.defineProperty(playbackService, 'next', {
      get: () => null
    })
    preferences.repeatMode = 'NO_REPEAT'
    const stopMock = mock(playbackService, 'stop')

    await playbackService.playNext()
    expect(stopMock).toHaveBeenCalled()
  })

  it('plays the next song', async () => {
    const nextSong = factory('song')
    Object.defineProperty(playbackService, 'next', {
      get: () => nextSong
    })
    const playMock = mock(playbackService, 'play')

    await playbackService.playNext()
    expect(playMock).toHaveBeenCalledWith(nextSong)
  })

  it('stops playback', () => {
    const currentSong = factory<Song>('song')
    const pauseMock = mock(playbackService.player!, 'pause')
    const seekMock = mock(playbackService.player!, 'seek')
    Object.defineProperty(queueStore, 'current', {
      get: () => currentSong
    })
    const broadcastMock = mock(socketService, 'broadcast')

    playbackService.stop()
    expect(currentSong.playbackState).toEqual('Stopped')
    expect(pauseMock).toHaveBeenCalled()
    expect(seekMock).toHaveBeenCalledWith(0)
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_PLAYBACK_STOPPED')
    expect(document.title).toEqual('Koel')
  })

  it('pauses playback', () => {
    const currentSong = factory<Song>('song')
    Object.defineProperty(queueStore, 'current', {
      get: () => currentSong
    })
    const dataToBroadcast = {}
    mock(songStore, 'generateDataToBroadcast', dataToBroadcast)
    const pauseMock = mock(playbackService.player!, 'pause')
    const broadcastMock = mock(socketService, 'broadcast')

    playbackService.pause()
    expect(currentSong.playbackState).toEqual('Paused')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_SONG', dataToBroadcast)
    expect(pauseMock).toHaveBeenCalled()
  })

  it('resumes playback', async () => {
    const currentSong = factory<Song>('song')
    Object.defineProperty(queueStore, 'current', {
      get: () => currentSong
    })
    const dataToBroadcast = {}
    mock(songStore, 'generateDataToBroadcast', dataToBroadcast)
    const playMock = mock(window.HTMLMediaElement.prototype, 'play')
    const broadcastMock = mock(socketService, 'broadcast')
    const emitMock = mock(eventBus, 'emit')

    playbackService.init()
    await playbackService.resume()
    expect(queueStore.current?.playbackState).toEqual('Playing')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_SONG', dataToBroadcast)
    expect(playMock).toHaveBeenCalled()
    expect(emitMock).toHaveBeenCalledWith('SONG_STARTED', currentSong)
  })

  it('plays first in queue if toggled when there is no current song', async () => {
    const playFirstInQueueMock = mock(playbackService, 'playFirstInQueue')
    Object.defineProperty(queueStore, 'current', {
      get: () => null
    })
    await playbackService.toggle()
    expect(playFirstInQueueMock).toHaveBeenCalled()
  })

  it.each<[FunctionPropertyNames<typeof playbackService>, PlaybackState]>([
    ['resume', 'Stopped'],
    ['resume', 'Paused'],
    ['pause', 'Playing']
  ])('%ss playback if toggled when current song playback state is %s', async (action, playbackState) => {
    const actionMock = mock(playbackService, action)
    Object.defineProperty(queueStore, 'current', {
      get: () => factory('song', { playbackState })
    })
    await playbackService.toggle()
    expect(actionMock).toHaveBeenCalled()
  })

  it('queues and plays all songs shuffled by default', async () => {
    const allSongs = factory<Song>('song', 5)
    const shuffledSongs = factory<Song>('song', 5)
    Object.defineProperty(songStore, 'all', {
      get: () => allSongs
    })

    const firstSongInQueue = factory('song')
    Object.defineProperty(queueStore, 'first', {
      get: () => firstSongInQueue
    })

    const replaceQueueMock = mock(queueStore, 'replaceQueueWith')
    const goMock = mock(router, 'go')
    const playMock = mock(playbackService, 'play')
    ;(shuffle as jest.Mock).mockReturnValue(shuffledSongs)

    await playbackService.queueAndPlay()
    await Vue.nextTick()
    expect(shuffle).toHaveBeenCalledWith(allSongs)
    expect(replaceQueueMock).toHaveBeenCalledWith(shuffledSongs)
    expect(goMock).toHaveBeenCalledWith('queue')
    expect(playMock).toHaveBeenCalledWith(firstSongInQueue)
  })

  it('queues and plays songs without shuffling', async () => {
    const songs = factory<Song>('song', 5)
    const replaceQueueMock = mock(queueStore, 'replaceQueueWith')
    const goMock = mock(router, 'go')
    const playMock = mock(playbackService, 'play')
    const firstSongInQueue = songs[0]
    Object.defineProperty(queueStore, 'first', {
      get: () => firstSongInQueue
    })

    await playbackService.queueAndPlay(songs)
    await Vue.nextTick()
    expect(shuffle).not.toHaveBeenCalled()
    expect(replaceQueueMock).toHaveBeenCalledWith(songs)
    expect(goMock).toHaveBeenCalledWith('queue')
    expect(playMock).toHaveBeenCalledWith(firstSongInQueue)
  })

  it('queues and plays songs with shuffling', async () => {
    const songs = factory<Song>('song', 5)
    const shuffledSongs = factory<Song>('song', 5)
    const replaceQueueMock = mock(queueStore, 'replaceQueueWith')
    const goMock = mock(router, 'go')
    const playMock = mock(playbackService, 'play')
    const firstSongInQueue = songs[0]
    Object.defineProperty(queueStore, 'first', {
      get: () => firstSongInQueue
    })
    ;(shuffle as jest.Mock).mockReturnValue(shuffledSongs)

    await playbackService.queueAndPlay(songs, true)
    await Vue.nextTick()
    expect(shuffle).toHaveBeenCalledWith(songs)
    expect(replaceQueueMock).toHaveBeenCalledWith(shuffledSongs)
    expect(goMock).toHaveBeenCalledWith('queue')
    expect(playMock).toHaveBeenCalledWith(firstSongInQueue)
  })

  it('plays first song in queue', async () => {
    const songs = factory<Song>('song', 5)
    queueStore.all = songs
    Object.defineProperty(queueStore, 'first', {
      get: () => songs[0]
    })
    const playMock = mock(playbackService, 'play')

    await playbackService.playFirstInQueue()
    expect(playMock).toHaveBeenCalledWith(songs[0])
  })

  it('playFirstInQueue triggers queueAndPlay if queue is empty', async () => {
    queueStore.all = []
    const queueAndPlayMock = mock(playbackService, 'queueAndPlay')

    await playbackService.playFirstInQueue()
    expect(queueAndPlayMock).toHaveBeenCalled()
  })

  it('plays all songs by an artist, shuffled', async () => {
    const artist = factory<Artist>('artist', {
      songs: factory<Song>('song', 5)
    })
    const queueAndPlayMock = mock(playbackService, 'queueAndPlay')

    await playbackService.playAllByArtist(artist)
    expect(queueAndPlayMock).toHaveBeenCalledWith(artist.songs, true)
  })

  it('plays all songs by an artist in proper order', async () => {
    const artist = factory<Artist>('artist', {
      songs: factory<Song>('song', 5)
    })
    const orderedSongs = factory('song', 5)
    ;(orderBy as jest.Mock).mockReturnValue(orderedSongs)

    const queueAndPlayMock = mock(playbackService, 'queueAndPlay')
    await playbackService.playAllByArtist(artist, false)
    expect(orderBy).toHaveBeenCalledWith(artist.songs, ['album_id', 'disc', 'track'])
    expect(queueAndPlayMock).toHaveBeenCalledWith(orderedSongs)
  })

  it('plays all songs in an album, shuffled', async () => {
    const album = factory<Album>('album', {
      songs: factory<Song>('song', 5)
    })
    const queueAndPlayMock = mock(playbackService, 'queueAndPlay')

    await playbackService.playAllInAlbum(album)
    expect(queueAndPlayMock).toHaveBeenCalledWith(album.songs, true)
  })

  it('plays all songs in an album in proper order', async () => {
    const album = factory<Album>('album', {
      songs: factory<Song>('song', 5)
    })
    const orderedSongs = factory('song', 5)
    ;(orderBy as jest.Mock).mockReturnValue(orderedSongs)

    const queueAndPlayMock = mock(playbackService, 'queueAndPlay')
    await playbackService.playAllInAlbum(album, false)
    expect(orderBy).toHaveBeenCalledWith(album.songs, ['disc', 'track'])
    expect(queueAndPlayMock).toHaveBeenCalledWith(orderedSongs)
  })
})
