import plyr from 'plyr'
import { shuffle, orderBy } from 'lodash'
import { playback, socket } from '@/services'
import { eventBus, noop } from '@/utils'
import { mock } from '@/__tests__/__helpers__'
import {
  queueStore,
  sharedStore,
  userStore,
  songStore,
  recentlyPlayedStore,
  preferenceStore as preferences
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
    playback.init()
    expect(plyrSetupSpy).toHaveBeenCalled()
    playback.init()
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
        Object.defineProperty(playback, 'isTranscoding', { get: () => isTranscoding })
        playback.init()
        const mediaElement = playback.player!.media
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

        const registerPlayMock = mock(playback, 'registerPlay')
        mediaElement.dispatchEvent(new Event('timeupdate'))
        expect(registerPlayMock).toHaveBeenCalledTimes(numberOfCalls)
      })

    it('plays next song if current song is errored', () => {
      playback.init()
      const playNextMock = mock(playback, 'playNext')
      playback.player!.media.dispatchEvent(new Event('error'))
      expect(playNextMock).toHaveBeenCalled()
    })

    it('scrobbles if current song ends', () => {
      queueStore.current = factory<Song>('song')
      sharedStore.state.useLastfm = true
      userStore.current = factory<User>('user', {
        preferences: {
          lastfm_session_key: 'foo'
        }
      })

      playback.init()
      const scrobbleMock = mock(songStore, 'scrobble')
      playback.player!.media.dispatchEvent(new Event('ended'))
      expect(scrobbleMock).toHaveBeenCalled()
    })

    it.each<[RepeatMode, number, number]>([['REPEAT_ONE', 1, 0], ['NO_REPEAT', 0, 1], ['REPEAT_ALL', 0, 1]])(
      'when song ends, if repeat mode is %s then restart() is called %d times and playNext() is called %d times',
      (repeatMode, restartCalls, playNextCalls) => {
        sharedStore.state.useLastfm = false // so that no scrobbling is made unnecessarily
        preferences.repeatMode = repeatMode
        playback.init()
        const restartMock = mock(playback, 'restart')
        const playNextMock = mock(playback, 'playNext')
        playback.player!.media.dispatchEvent(new Event('ended'))
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
        Object.defineProperty(playback, 'isTranscoding', { get: () => isTranscoding })
        playback.init()
        const mediaElement = playback.player!.media
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

        const preloadMock = mock(playback, 'preload')
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
    playback.registerPlay(song)
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
    playback.preload(song)
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
    const broadcastMock = mock(socket, 'broadcast')
    const showNotificationMock = mock(playback, 'showNotification')
    const dataToBroadcast = {}
    mock(songStore, 'generateDataToBroadcast', dataToBroadcast)
    const restartMock = mock(playback.player!, 'restart')
    const playMock = mock(window.HTMLMediaElement.prototype, 'play')

    await playback.restart()
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
  ])('it switches from repeat mode %s to repeat mode %s', (fromMode, toMode) => {
      preferences.repeatMode = fromMode

      playback.changeRepeatMode()
      expect(preferences.repeatMode).toEqual(toMode)
    }
  )

  it('restarts song if playPrev is triggered after 5 seconds', async () => {
    const restartMock = mock(playback.player!, 'restart')
    Object.defineProperty(playback.player!.media, 'currentTime', {
      get: () => 6
    })
    Object.defineProperty(queueStore, 'current', {
      get: () => factory('song', { length: 120 })
    })

    await playback.playPrev()
    expect(restartMock).toHaveBeenCalled()
  })

  it('stops if playPrev is triggered when there is no prev song and repeat mode is NO_REPEAT', async () => {
    const stopMock = mock(playback, 'stop')
    Object.defineProperty(playback.player!.media, 'currentTime', {
      get: () => 4
    })
    Object.defineProperty(queueStore, 'current', {
      get: () => factory('song', { length: 120 })
    })
    Object.defineProperty(playback, 'previous', {
      get: () => null
    })
    preferences.repeatMode = 'NO_REPEAT'

    await playback.playPrev()
    expect(stopMock).toHaveBeenCalled()
  })

  it('plays the previous song', async () => {
    const previousSong = factory('song')
    Object.defineProperty(playback, 'previous', {
      get: () => previousSong
    })
    Object.defineProperty(playback.player!.media, 'currentTime', {
      get: () => 4
    })
    Object.defineProperty(queueStore, 'current', {
      get: () => factory('song', { length: 120 })
    })
    const playMock = mock(playback, 'play')

    await playback.playPrev()
    expect(playMock).toHaveBeenCalledWith(previousSong)
  })

  it('stops if playNext is triggered when there is no next song and repeat mode is NO_REPEAT', async () => {
    Object.defineProperty(playback, 'next', {
      get: () => null
    })
    preferences.repeatMode = 'NO_REPEAT'
    const stopMock = mock(playback, 'stop')

    await playback.playNext()
    expect(stopMock).toHaveBeenCalled()
  })

  it('plays the next song', async () => {
    const nextSong = factory('song')
    Object.defineProperty(playback, 'next', {
      get: () => nextSong
    })
    const playMock = mock(playback, 'play')

    await playback.playNext()
    expect(playMock).toHaveBeenCalledWith(nextSong)
  })

  it('stops playback', () => {
    const currentSong = factory<Song>('song')
    const pauseMock = mock(playback.player!, 'pause')
    const seekMock = mock(playback.player!, 'seek')
    Object.defineProperty(queueStore, 'current', {
      get: () => currentSong
    })
    const broadcastMock = mock(socket, 'broadcast')

    playback.stop()
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
    const pauseMock = mock(playback.player!, 'pause')
    const broadcastMock = mock(socket, 'broadcast')

    playback.pause()
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
    const broadcastMock = mock(socket, 'broadcast')
    const emitMock = mock(eventBus, 'emit')

    playback.init()
    await playback.resume()
    expect(queueStore.current?.playbackState).toEqual('Playing')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_SONG', dataToBroadcast)
    expect(playMock).toHaveBeenCalled()
    expect(emitMock).toHaveBeenCalledWith('SONG_STARTED', currentSong)
  })

  it('plays first in queue if toggled when there is no current song', async () => {
    const playFirstInQueueMock = mock(playback, 'playFirstInQueue')
    Object.defineProperty(queueStore, 'current', {
      get: () => null
    })
    await playback.toggle()
    expect(playFirstInQueueMock).toHaveBeenCalled()
  })

  it.each<[FunctionPropertyNames<typeof playback>, PlaybackState]>([
    ['resume', 'Stopped'],
    ['resume', 'Paused'],
    ['pause', 'Playing']
  ])('%ss playback if toggled when current song playback state is %s', async (action, playbackState) => {
    const actionMock = mock(playback, action)
    Object.defineProperty(queueStore, 'current', {
      get: () => factory('song', { playbackState })
    })
    await playback.toggle()
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
    const playMock = mock(playback, 'play')
    ;(shuffle as jest.Mock).mockReturnValue(shuffledSongs)

    await playback.queueAndPlay()
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
    const playMock = mock(playback, 'play')
    const firstSongInQueue = songs[0]
    Object.defineProperty(queueStore, 'first', {
      get: () => firstSongInQueue
    })

    await playback.queueAndPlay(songs)
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
    const playMock = mock(playback, 'play')
    const firstSongInQueue = songs[0]
    Object.defineProperty(queueStore, 'first', {
      get: () => firstSongInQueue
    })
    ;(shuffle as jest.Mock).mockReturnValue(shuffledSongs)

    await playback.queueAndPlay(songs, true)
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
    const playMock = mock(playback, 'play')

    await playback.playFirstInQueue()
    expect(playMock).toHaveBeenCalledWith(songs[0])
  })

  it('playFirstInQueue triggers queueAndPlay if queue is empty', async () => {
    queueStore.all = []
    const queueAndPlayMock = mock(playback, 'queueAndPlay')

    await playback.playFirstInQueue()
    expect(queueAndPlayMock).toHaveBeenCalled()
  })

  it('plays all songs by an artist, shuffled', async () => {
    const artist = factory<Artist>('artist', {
      songs: factory<Song>('song', 5)
    })
    const queueAndPlayMock = mock(playback, 'queueAndPlay')

    await playback.playAllByArtist(artist)
    expect(queueAndPlayMock).toHaveBeenCalledWith(artist.songs, true)
  })

  it('plays all songs by an artist in proper order', async () => {
    const artist = factory<Artist>('artist', {
      songs: factory<Song>('song', 5)
    })
    const orderedSongs = factory('song', 5)
    ;(orderBy as jest.Mock).mockReturnValue(orderedSongs)

    const queueAndPlayMock = mock(playback, 'queueAndPlay')
    await playback.playAllByArtist(artist, false)
    expect(orderBy).toHaveBeenCalledWith(artist.songs, ['album_id', 'disc', 'track'])
    expect(queueAndPlayMock).toHaveBeenCalledWith(orderedSongs)
  })

  it('plays all songs in an album, shuffled', async () => {
    const album = factory<Album>('album', {
      songs: factory<Song>('song', 5)
    })
    const queueAndPlayMock = mock(playback, 'queueAndPlay')

    await playback.playAllInAlbum(album)
    expect(queueAndPlayMock).toHaveBeenCalledWith(album.songs, true)
  })

  it('plays all songs in an album in proper order', async () => {
    const album = factory<Album>('album', {
      songs: factory<Song>('song', 5)
    })
    const orderedSongs = factory('song', 5)
    ;(orderBy as jest.Mock).mockReturnValue(orderedSongs)

    const queueAndPlayMock = mock(playback, 'queueAndPlay')
    await playback.playAllInAlbum(album, false)
    expect(orderBy).toHaveBeenCalledWith(album.songs, ['disc', 'track'])
    expect(queueAndPlayMock).toHaveBeenCalledWith(orderedSongs)
  })
})
