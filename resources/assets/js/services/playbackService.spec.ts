import plyr from 'plyr'
import lodash from 'lodash'
import { expect, it, vi } from 'vitest'
import { eventBus, noop } from '@/utils'
import router from '@/router'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { nextTick } from 'vue'
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
        queueStore.current = factory<Song>('song', { playCountRegistered })
        this.setReadOnlyProperty(playbackService, 'isTranscoding', isTranscoding)
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
      queueStore.current = factory<Song>('song')
      commonStore.state.useLastfm = true
      userStore.current = factory<User>('user', {
        preferences: {
          lastfm_session_key: 'foo'
        }
      })

      playbackService.init()
      const scrobbleMock = this.mock(songStore, 'scrobble')
      playbackService.player!.media.dispatchEvent(new Event('ended'))
      expect(scrobbleMock).toHaveBeenCalled()
    })

    it.each<[RepeatMode, number, number]>([['REPEAT_ONE', 1, 0], ['NO_REPEAT', 0, 1], ['REPEAT_ALL', 0, 1]])(
      'when song ends, if repeat mode is %s then restart() is called %d times and playNext() is called %d times',
      (repeatMode, restartCalls, playNextCalls) => {
        commonStore.state.useLastfm = false // so that no scrobbling is made unnecessarily
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
        queueStore.current = factory<Song>('song', { playCountRegistered: true }) // avoid triggering play count logic
        this.setReadOnlyProperty(queueStore, 'next', factory<Song>('song', { preloaded }))
        this.setReadOnlyProperty(playbackService, 'isTranscoding', isTranscoding)
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

        const preloadMock = this.mock(playbackService, 'preload')
        mediaElement.dispatchEvent(new Event('timeupdate'))

        expect(preloadMock).toHaveBeenCalledTimes(numberOfCalls)
      }
    )

    it('registers play', () => {
      const recentlyPlayedStoreAddMock = this.mock(recentlyPlayedStore, 'add')
      const recentlyPlayedStoreFetchAllMock = this.mock(recentlyPlayedStore, 'fetchAll')
      const registerPlayMock = this.mock(songStore, 'registerPlay')
      const song = factory<Song>('song')

      playbackService.registerPlay(song)

      expect(recentlyPlayedStoreAddMock).toHaveBeenCalledWith(song)
      expect(recentlyPlayedStoreFetchAllMock).toHaveBeenCalled()
      expect(registerPlayMock).toHaveBeenCalledWith(song)
      expect(song.playCountRegistered).toBe(true)
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
      const song = factory<Song>('song')
      queueStore.current = song
      this.mock(Math, 'floor', 1000)
      const emitMock = this.mock(eventBus, 'emit')
      const broadcastMock = this.mock(socketService, 'broadcast')
      const showNotificationMock = this.mock(playbackService, 'showNotification')
      const dataToBroadcast = {}
      this.mock(songStore, 'generateDataToBroadcast', dataToBroadcast)
      const restartMock = this.mock(playbackService.player!, 'restart')
      const playMock = this.mock(window.HTMLMediaElement.prototype, 'play')

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
    ])('it switches from repeat mode %s to repeat mode %s', (fromMode, toMode) => {
      preferences.repeatMode = fromMode
      playbackService.changeRepeatMode()

      expect(preferences.repeatMode).toEqual(toMode)
    })

    it('restarts song if playPrev is triggered after 5 seconds', async () => {
      const mock = this.mock(playbackService.player!, 'restart')
      this.setReadOnlyProperty(playbackService.player!.media, 'currentTime', 6)
      queueStore.current = factory<Song>('song', { length: 120 })

      await playbackService.playPrev()

      expect(mock).toHaveBeenCalled()
    })

    it('stops if playPrev is triggered when there is no prev song and repeat mode is NO_REPEAT', async () => {
      const stopMock = this.mock(playbackService, 'stop')
      this.setReadOnlyProperty(playbackService.player!.media, 'currentTime', 4)
      this.setReadOnlyProperty(playbackService, 'previous', undefined)
      queueStore.current = factory<Song>('song')
      preferences.repeatMode = 'NO_REPEAT'

      await playbackService.playPrev()

      expect(stopMock).toHaveBeenCalled()
    })

    it('plays the previous song', async () => {
      const previousSong = factory('song')
      this.setReadOnlyProperty(playbackService.player!.media, 'currentTime', 4)
      this.setReadOnlyProperty(playbackService, 'previous', previousSong)
      queueStore.current = factory<Song>('song')
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
      queueStore.current = currentSong
      const pauseMock = this.mock(playbackService.player!, 'pause')
      const seekMock = this.mock(playbackService.player!, 'seek')
      const broadcastMock = this.mock(socketService, 'broadcast')

      playbackService.stop()

      expect(currentSong.playbackState).toEqual('Stopped')
      expect(pauseMock).toHaveBeenCalled()
      expect(seekMock).toHaveBeenCalledWith(0)
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_PLAYBACK_STOPPED')
      expect(document.title).toEqual('Koel')
    })

    it('pauses playback', () => {
      const currentSong = factory<Song>('song')
      queueStore.current = currentSong
      const dataToBroadcast = {}
      this.mock(songStore, 'generateDataToBroadcast', dataToBroadcast)
      const pauseMock = this.mock(playbackService.player!, 'pause')
      const broadcastMock = this.mock(socketService, 'broadcast')

      playbackService.pause()

      expect(currentSong.playbackState).toEqual('Paused')
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_SONG', dataToBroadcast)
      expect(pauseMock).toHaveBeenCalled()
    })

    it('resumes playback', async () => {
      const currentSong = factory<Song>('song')
      queueStore.current = currentSong
      const dataToBroadcast = {}
      this.mock(songStore, 'generateDataToBroadcast', dataToBroadcast)
      const playMock = this.mock(window.HTMLMediaElement.prototype, 'play')
      const broadcastMock = this.mock(socketService, 'broadcast')
      const emitMock = this.mock(eventBus, 'emit')

      playbackService.init()
      await playbackService.resume()

      expect(queueStore.current?.playbackState).toEqual('Playing')
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_SONG', dataToBroadcast)
      expect(playMock).toHaveBeenCalled()
      expect(emitMock).toHaveBeenCalledWith('SONG_STARTED', currentSong)
    })

    it('plays first in queue if toggled when there is no current song', async () => {
      const playFirstInQueueMock = this.mock(playbackService, 'playFirstInQueue')
      queueStore.current = undefined

      await playbackService.toggle()

      expect(playFirstInQueueMock).toHaveBeenCalled()
    })

    it.each<[MethodOf<typeof playbackService>, PlaybackState]>([
      ['resume', 'Stopped'],
      ['resume', 'Paused'],
      ['pause', 'Playing']
    ])('%ss playback if toggled when current song playback state is %s', async (action, playbackState) => {
      const actionMock = this.mock(playbackService, action)
      queueStore.current = factory<Song>('song', { playbackState })

      await playbackService.toggle()

      expect(actionMock).toHaveBeenCalled()
    })

    it('queues and plays all songs shuffled by default', async () => {
      const allSongs = factory<Song>('song', 5)
      const shuffledSongs = factory<Song>('song', 5)
      songStore.all = allSongs
      const firstSongInQueue = factory('song')
      this.setReadOnlyProperty(queueStore, 'first', firstSongInQueue)
      const replaceQueueMock = this.mock(queueStore, 'replaceQueueWith')
      const goMock = this.mock(router, 'go')
      const playMock = this.mock(playbackService, 'play')
      const shuffleMock = this.mock(lodash, 'shuffle', shuffledSongs)

      await playbackService.queueAndPlay()
      await nextTick()

      expect(shuffleMock).toHaveBeenCalledWith(allSongs)
      expect(replaceQueueMock).toHaveBeenCalledWith(shuffledSongs)
      expect(goMock).toHaveBeenCalledWith('queue')
      expect(playMock).toHaveBeenCalledWith(firstSongInQueue)
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

    it('playFirstInQueue triggers queueAndPlay if queue is empty', async () => {
      queueStore.all = []
      const queueAndPlayMock = this.mock(playbackService, 'queueAndPlay')

      await playbackService.playFirstInQueue()

      expect(queueAndPlayMock).toHaveBeenCalled()
    })

    it('plays all songs by an artist, shuffled', async () => {
      const artist = factory<Artist>('artist', {
        songs: factory<Song>('song', 5)
      })

      const queueAndPlayMock = this.mock(playbackService, 'queueAndPlay')

      await playbackService.playAllByArtist(artist)

      expect(queueAndPlayMock).toHaveBeenCalledWith(artist.songs, true)
    })

    it('plays all songs by an artist in proper order', async () => {
      const artist = factory<Artist>('artist', {
        songs: factory<Song>('song', 5)
      })

      const orderedSongs = factory('song', 5)
      const orderByMock = this.mock(lodash, 'orderBy', orderedSongs)
      const queueAndPlayMock = this.mock(playbackService, 'queueAndPlay')

      await playbackService.playAllByArtist(artist, false)

      expect(orderByMock).toHaveBeenCalledWith(artist.songs, ['album_id', 'disc', 'track'])
      expect(queueAndPlayMock).toHaveBeenCalledWith(orderedSongs)
    })

    it('plays all songs in an album, shuffled', async () => {
      const album = factory<Album>('album', {
        songs: factory<Song>('song', 5)
      })

      const queueAndPlayMock = this.mock(playbackService, 'queueAndPlay')

      await playbackService.playAllInAlbum(album)

      expect(queueAndPlayMock).toHaveBeenCalledWith(album.songs, true)
    })

    it('plays all songs in an album in proper order', async () => {
      const album = factory<Album>('album', {
        songs: factory<Song>('song', 5)
      })

      const orderedSongs = factory('song', 5)
      const orderByMock = this.mock(lodash, 'orderBy', orderedSongs)
      const queueAndPlayMock = this.mock(playbackService, 'queueAndPlay')

      await playbackService.playAllInAlbum(album, false)

      expect(orderByMock).toHaveBeenCalledWith(album.songs, ['disc', 'track'])
      expect(queueAndPlayMock).toHaveBeenCalledWith(orderedSongs)
    })
  }
}
