import { nextTick, reactive } from 'vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import * as lodash from 'lodash-es'
import { createHarness } from '@/__tests__/TestHarness'

vi.mock('lodash-es', async importOriginal => {
  const mod = await importOriginal<typeof lodash>()
  return { ...mod, shuffle: vi.fn(mod.shuffle) }
})
import { http } from '@/services/http'
import { socketService } from '@/services/socketService'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { userStore } from '@/stores/userStore'
import { commonStore } from '@/stores/commonStore'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { logger } from '@/utils/logger'
import { audioService } from '@/services/audioService'
import { playbackService } from '@/services/QueuePlaybackService'
import { crossfadeService } from '@/services/crossfadeService'
import { volumeManager } from '@/services/volumeManager'

describe('playbackService', () => {
  const h = createHarness({
    beforeEach: () => {
      playableStore.vault.clear()
      queueStore.state.playables = []
      h.createAudioPlayer()
      playbackService.activate(document.querySelector<HTMLMediaElement>('#audio-player')!)
    },
    afterEach: () => {
      playbackService.deactivate()
      audioService.context = null!
      preferences.temporary.crossfade_duration = 0
      vi.useRealTimers()
    },
  })

  const setCurrentSong = (song?: Playable) => {
    const playbackState = song?.playback_state ?? 'Playing'
    const [synced] = playableStore.syncWithVault(song || h.factory('song').make())
    synced.playback_state = playbackState
    queueStore.state.playables = reactive([synced])
    return synced
  }

  const enableProgressivePlayback = () => {
    commonStore.state.cdn_url = 'http://test/'
    commonStore.state.supports_progressive_transcoding = true
    h.setReadOnlyProperty(navigator, 'onLine', true)
    h.mock(HTMLMediaElement.prototype, 'canPlayType', 'probably')
  }

  const dispatchLoadedMetadata = (duration: number) => {
    h.setReadOnlyProperty(playbackService.media, 'duration', duration)
    playbackService.media.dispatchEvent(new Event('loadedmetadata'))
  }

  it('only initializes once', () => {
    const media = playbackService.media
    playbackService.activate(document.querySelector<HTMLMediaElement>('#audio-player')!)
    // media reference should remain the same (not re-initialized)
    expect(playbackService.media).toBe(media)
  })

  it.each([
    [false, 100, 400, 1],
    [true, 100, 400, 0],
    [false, 100, 500, 0],
  ])(
    'when playCountRegistered is %s, current media time is %d, media duration is %d, then registerPlay() should be call %d times',
    (playCountRegistered, currentTime, duration, numberOfCalls) => {
      const song = h.factory('song').make({
        play_count_registered: playCountRegistered,
        playback_state: 'Playing',
      })

      setCurrentSong(song)

      const mediaElement = playbackService.media

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
    playbackService.media.dispatchEvent(errorEvent)

    expect(playNextMock).toHaveBeenCalled()
    expect(logMock).toHaveBeenCalledWith(errorEvent)
  })

  it('scrobbles if current playable ends', () => {
    setCurrentSong()
    commonStore.state.uses_last_fm = true
    userStore.state.current.preferences.lastfm_session_key = 'foo'

    const scrobbleMock = h.mock(playableStore, 'scrobble')
    playbackService.media.dispatchEvent(new Event('ended'))
    expect(scrobbleMock).toHaveBeenCalled()
  })

  it.each<[RepeatMode, number, number]>([
    ['REPEAT_ONE', 1, 0],
    ['NO_REPEAT', 0, 1],
    ['REPEAT_ALL', 0, 1],
  ])(
    'when playable ends, if repeat mode is %s then restart() is called %d times and playNext() is called %d times',
    (repeatMode, restartCalls, playNextCalls) => {
      setCurrentSong()

      const restartMock = h.mock(playbackService, 'restart')
      const playNextMock = h.mock(playbackService, 'playNext')

      commonStore.state.uses_last_fm = false // so that no scrobbling is made unnecessarily
      preferences.temporary.repeat_mode = repeatMode

      playbackService.media.dispatchEvent(new Event('ended'))

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
      setCurrentSong()
      h.mock(playbackService, 'registerPlay')
      h.setReadOnlyProperty(queueStore, 'next', h.factory('song').make({ preloaded }))

      const mediaElement = playbackService.media

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
    const song = h.factory('song').make()

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
    const song = h.factory('song').make()

    playbackService.preload(song)

    expect(createElementMock).toHaveBeenCalledWith('audio')
    expect(audioElement.setAttribute).toHaveBeenNthCalledWith(1, 'src', '/foo?token=o5afd')
    expect(audioElement.setAttribute).toHaveBeenNthCalledWith(2, 'preload', 'auto')
    expect(audioElement.load).toHaveBeenCalled()
    expect(song.preloaded).toBe(true)
  })

  it('reuses a preloaded progressive stream for normal playback', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    const originalCreateElement = document.createElement.bind(document)
    const createdAudioElements: HTMLAudioElement[] = []

    h.mock(document, 'createElement', (tagName: string) => {
      const element = originalCreateElement(tagName)

      if (element instanceof HTMLAudioElement) {
        createdAudioElements.push(element)
      }

      return element
    })
    h.mock(playbackService, 'restart')

    playbackService.preload(song)
    const preloadedAudio = createdAudioElements.find(media => media.src.includes('progressive=1'))!
    await playbackService.play(song)

    expect(playbackService.media).toBe(preloadedAudio)
    expect(preloadedAudio.src).toContain('progressive=1')
    expect(song.preloaded).toBe(false)
  })

  it('disposes a retained progressive preload when another song starts', async () => {
    enableProgressivePlayback()
    const preloadedSong = h.factory('song').make({ requires_transcoding: true, length: 400 })
    const playedSong = h.factory('song').make({ requires_transcoding: true, length: 400 })
    const originalCreateElement = document.createElement.bind(document)
    const createdAudioElements: HTMLAudioElement[] = []

    h.mock(document, 'createElement', (tagName: string) => {
      const element = originalCreateElement(tagName)

      if (element instanceof HTMLAudioElement) {
        createdAudioElements.push(element)
      }

      return element
    })
    h.mock(playbackService, 'restart')

    playbackService.preload(preloadedSong)
    const preloadedAudio = createdAudioElements.find(media => media.src.includes(preloadedSong.id))!
    await playbackService.play(playedSong)

    expect(preloadedAudio.src).toBe('')
    expect(preloadedSong.preloaded).toBe(false)
    expect(playbackService.media).not.toBe(preloadedAudio)
    expect(playbackService.media.src).toContain(playedSong.id)
  })

  it('does not adopt a progressive preload with an already-latched error', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    const originalCreateElement = document.createElement.bind(document)
    const createdAudioElements: HTMLAudioElement[] = []

    h.mock(document, 'createElement', (tagName: string) => {
      const element = originalCreateElement(tagName)

      if (element instanceof HTMLAudioElement) {
        createdAudioElements.push(element)
      }

      return element
    })
    h.mock(playbackService, 'restart')

    playbackService.preload(song)
    const failedPreload = createdAudioElements.find(media => media.src.includes(song.id))!
    h.setReadOnlyProperty(failedPreload, 'error', { code: 3 })
    await playbackService.play(song)

    expect(song.preloaded).toBe(false)
    expect(playbackService.media).not.toBe(failedPreload)
    expect(playbackService.media.src).toContain(song.id)
    expect(playbackService.media.src).toContain('progressive=1')
  })

  it('retries progressive preloading after a detached preload errors', () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    const originalCreateElement = document.createElement.bind(document)
    const createdAudioElements: HTMLAudioElement[] = []

    h.mock(document, 'createElement', (tagName: string) => {
      const element = originalCreateElement(tagName)

      if (element instanceof HTMLAudioElement) {
        createdAudioElements.push(element)
      }

      return element
    })

    playbackService.preload(song)
    const failedPreload = createdAudioElements.find(media => media.src.includes(song.id))!
    failedPreload.dispatchEvent(new Event('error'))
    playbackService.preload(song)

    const preloadRequests = createdAudioElements.filter(media => media.preload === 'auto')

    expect(preloadRequests).toHaveLength(2)
    expect(failedPreload.src).toBe('')
    expect(preloadRequests[1].src).toContain(song.id)
    expect(song.preloaded).toBe(true)
  })

  it('restarts a playable', async () => {
    const song = setCurrentSong()
    h.mock(Math, 'floor', 1000)
    const broadcastMock = h.mock(socketService, 'broadcast')
    const showNotificationMock = h.mock(playbackService, 'showNotification')
    const putMock = h.mock(http, 'put')
    const playMock = h.mock(window.HTMLMediaElement.prototype, 'play')

    await playbackService.restart()

    expect(song.play_start_time).toEqual(1000)
    expect(song.play_count_registered).toBe(false)
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', song)
    expect(showNotificationMock).toHaveBeenCalled()
    expect(playbackService.media.currentTime).toBe(0)
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

    h.setReadOnlyProperty(playbackService.media, 'currentTime', 6)

    await playbackService.playPrev()

    expect(playbackService.media.currentTime).toBe(0)
  })

  it('stops if playPrev is triggered when there is no prev playable and repeat mode is NO_REPEAT', async () => {
    const stopMock = h.mock(playbackService, 'stop')
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 4)
    h.setReadOnlyProperty(playbackService, 'previous', undefined)
    preferences.temporary.repeat_mode = 'NO_REPEAT'

    await playbackService.playPrev()

    expect(stopMock).toHaveBeenCalled()
  })

  it('plays the previous playable', async () => {
    const previousSong = h.factory('song').make()
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 4)
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
    const nextSong = h.factory('song').make()
    h.setReadOnlyProperty(playbackService, 'next', nextSong)
    const playMock = h.mock(playbackService, 'play')

    await playbackService.playNext()

    expect(playMock).toHaveBeenCalledWith(nextSong)
  })

  it('stops playback', () => {
    const currentSong = setCurrentSong()
    const pauseMock = h.mock(playbackService.media, 'pause')
    const broadcastMock = h.mock(socketService, 'broadcast')

    playbackService.stop()

    expect(currentSong.playback_state).toEqual('Stopped')
    expect(pauseMock).toHaveBeenCalled()
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_PLAYBACK_STOPPED')
    expect(document.title).toEqual('Koel')
  })

  it('pauses playback', () => {
    const song = setCurrentSong()
    const pauseMock = h.mock(playbackService.media, 'pause')
    const broadcastMock = h.mock(socketService, 'broadcast')

    playbackService.pause()

    expect(song.playback_state).toEqual('Paused')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', song)
    expect(pauseMock).toHaveBeenCalled()
  })

  it('resumes playback', async () => {
    const song = setCurrentSong(
      h.factory('song').make({
        playback_state: 'Paused',
      }),
    )

    const playMock = h.mock(window.HTMLMediaElement.prototype, 'play')
    const broadcastMock = h.mock(socketService, 'broadcast')

    await playbackService.resume()

    expect(queueStore.current?.playback_state).toEqual('Playing')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', song)
    expect(playMock).toHaveBeenCalled()
  })

  it('plays first in queue if toggled when there is no current playable', async () => {
    queueStore.state.playables = []
    playableStore.vault.clear()
    const playFirstInQueueMock = h.mock(playbackService, 'playFirstInQueue')

    await playbackService.toggle()

    expect(playFirstInQueueMock).toHaveBeenCalled()
  })

  it.each<[MethodOf<typeof playbackService>, PlaybackState]>([
    ['resume', 'Paused'],
    ['pause', 'Playing'],
  ])('%ss playback if toggled when current playable playback state is %s', async (action, playbackState) => {
    setCurrentSong(h.factory('song').make({ playback_state: playbackState }))
    const actionMock = h.mock(playbackService, action)
    await playbackService.toggle()

    expect(actionMock).toHaveBeenCalled()
  })

  it('queues and plays songs without shuffling', async () => {
    const songs = h.factory('song').make(5)
    const replaceQueueMock = h.mock(queueStore, 'replaceQueueWith')
    const playMock = h.mock(playbackService, 'play')
    const firstSongInQueue = songs[0]
    h.setReadOnlyProperty(queueStore, 'first', firstSongInQueue)

    playbackService.queueAndPlay(songs)
    await nextTick()

    expect(lodash.shuffle).not.toHaveBeenCalled()
    expect(replaceQueueMock).toHaveBeenCalledWith(songs)
    expect(playMock).toHaveBeenCalledWith(firstSongInQueue)
  })

  it('queues and plays songs with shuffling', async () => {
    const songs = h.factory('song').make(5)
    const shuffledSongs = h.factory('song').make(5)
    const replaceQueueMock = h.mock(queueStore, 'replaceQueueWith')
    const playMock = h.mock(playbackService, 'play')
    const firstSongInQueue = songs[0]
    h.setReadOnlyProperty(queueStore, 'first', firstSongInQueue)
    vi.mocked(lodash.shuffle).mockReturnValue(shuffledSongs)

    playbackService.queueAndPlay(songs, true)
    await nextTick()

    expect(lodash.shuffle).toHaveBeenCalledWith(songs)
    expect(replaceQueueMock).toHaveBeenCalledWith(shuffledSongs)
    expect(playMock).toHaveBeenCalledWith(firstSongInQueue)
  })

  it('plays first playable in queue', async () => {
    const songs = h.factory('song').make(5)
    queueStore.state.playables = songs
    h.setReadOnlyProperty(queueStore, 'first', songs[0])
    const playMock = h.mock(playbackService, 'play')

    await playbackService.playFirstInQueue()

    expect(playMock).toHaveBeenCalledWith(songs[0])
  })

  it('opts eligible queue songs into progressive Opus playback', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    const restartMock = h.mock(playbackService, 'restart')

    await playbackService.play(song)

    expect(restartMock).toHaveBeenCalled()
    expect(playbackService.media.src).toContain('progressive=1')
    expect(playbackService.duration).toBe(400)
  })

  it('resumes an infinite progressive stream without a native seek', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    h.mock(playbackService, 'restart')
    await playbackService.play(song)

    const previousMedia = playbackService.media
    const playMock = h.mock(HTMLMediaElement.prototype, 'play')
    playMock.mockClear()

    playbackService.seekTo(123.5)
    dispatchLoadedMetadata(Number.POSITIVE_INFINITY)
    await h.tick()

    expect(playbackService.media).not.toBe(previousMedia)
    expect(playbackService.media.src).toContain('progressive=1')
    expect(playbackService.media.src).toContain('time=123.5')
    expect(playbackService.media.currentTime).toBe(0)
    expect(playMock).toHaveBeenCalledTimes(1)
  })

  it('native-seeks a completed progressive cache when metadata loads', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    h.mock(playbackService, 'restart')
    await playbackService.play(song)

    const playMock = h.mock(HTMLMediaElement.prototype, 'play')
    playMock.mockClear()
    playbackService.seekTo(90)
    dispatchLoadedMetadata(400)
    await h.tick()

    expect(playbackService.media.currentTime).toBe(90)
    expect(playMock).toHaveBeenCalledTimes(1)
  })

  it('keeps playing when scrub metadata arrives after the readiness timeout', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    h.mock(playbackService, 'restart')
    await playbackService.play(song)

    const playMock = h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    playMock.mockClear()
    vi.useFakeTimers()

    try {
      playbackService.seekTo(90)

      expect(playMock).toHaveBeenCalledTimes(1)

      await vi.advanceTimersByTimeAsync(10_001)
      dispatchLoadedMetadata(400)
      await h.tick()

      expect(playMock).toHaveBeenCalledTimes(1)
      expect(playbackService.media.currentTime).toBe(90)
    } finally {
      vi.useRealTimers()
    }
  })

  it('keeps a paused progressive song paused after scrubbing', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    h.mock(playbackService, 'restart')
    await playbackService.play(song)
    song.playback_state = 'Paused'

    const playMock = h.mock(HTMLMediaElement.prototype, 'play')
    playMock.mockClear()
    playbackService.seekTo(90)
    dispatchLoadedMetadata(400)
    await h.tick()

    expect(playbackService.media.src).toContain('time=90')
    expect(playbackService.media.currentTime).toBe(90)
    expect(playMock).not.toHaveBeenCalled()
  })

  it('initiates saved-position playback before metadata and native-seeks a completed cache when ready', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    const playMock = h.mock(HTMLMediaElement.prototype, 'play')
    playMock.mockClear()

    const playbackPromise = playbackService.play(song, 75)
    await h.tick()

    expect(playMock).toHaveBeenCalledTimes(1)
    expect(playbackService.media.currentTime).toBe(0)

    dispatchLoadedMetadata(400)
    await playbackPromise

    expect(playbackService.media.currentTime).toBe(75)
  })

  it('seeks after loading when a progressive stream falls back to the legacy source', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    h.mock(playbackService, 'restart')
    await playbackService.play(song)
    h.setReadOnlyProperty(navigator, 'onLine', false)

    playbackService.seekTo(80)
    dispatchLoadedMetadata(400)
    await h.tick()

    expect(playbackService.media.src).not.toContain('progressive=1')
    expect(playbackService.media.currentTime).toBe(80)
  })

  it('ignores metadata from a superseded progressive seek', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    h.mock(playbackService, 'restart')
    await playbackService.play(song)
    const playMock = h.mock(HTMLMediaElement.prototype, 'play')
    playMock.mockClear()

    playbackService.seekTo(50)
    const supersededMedia = playbackService.media
    playbackService.seekTo(70)
    h.setReadOnlyProperty(supersededMedia, 'duration', 400)
    supersededMedia.dispatchEvent(new Event('loadedmetadata'))
    dispatchLoadedMetadata(400)
    await h.tick()

    expect(supersededMedia.currentTime).toBe(0)
    expect(playbackService.media.currentTime).toBe(70)
    expect(playMock).toHaveBeenCalledTimes(2)
  })

  it('uses absolute timestamps from progressive streams', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    h.mock(playbackService, 'restart')
    await playbackService.play(song)
    playbackService.seekTo(120)
    dispatchLoadedMetadata(Number.POSITIVE_INFINITY)
    await h.tick()

    h.setReadOnlyProperty(playbackService.media, 'currentTime', 120.5)

    expect(playbackService.position).toBe(120.5)
    expect(playbackService.duration).toBe(400)
  })

  it('adds the source offset when a browser restarts progressive timestamps at zero', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    h.mock(playbackService, 'restart')
    await playbackService.play(song)
    playbackService.seekTo(120)
    dispatchLoadedMetadata(Number.POSITIVE_INFINITY)
    await h.tick()

    h.setReadOnlyProperty(playbackService.media, 'currentTime', 10)

    expect(playbackService.position).toBe(130)
  })

  it('exposes logical buffered progress for relative progressive timestamps', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    h.mock(playbackService, 'restart')
    await playbackService.play(song)
    playbackService.seekTo(120)
    dispatchLoadedMetadata(Number.POSITIVE_INFINITY)
    await h.tick()
    h.setReadOnlyProperty(playbackService.media, 'buffered', {
      length: 1,
      end: () => 15,
    })

    expect(playbackService.bufferedThrough).toBe(135)
  })

  it('uses logical progressive time for play counts and saved positions', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({
      requires_transcoding: true,
      length: 400,
      play_count_registered: false,
    })
    h.mock(playbackService, 'restart')
    await playbackService.play(song)
    playbackService.seekTo(100)
    dispatchLoadedMetadata(Number.POSITIVE_INFINITY)
    await h.tick()
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 0)

    const registerPlayMock = h.mock(playbackService, 'registerPlay')
    const putMock = h.mock(http, 'put')
    playbackService.media.dispatchEvent(new Event('timeupdate'))

    expect(registerPlayMock).toHaveBeenCalledWith(song)
    expect(putMock).toHaveBeenCalledWith('queue/playback-status', {
      song: song.id,
      position: 100,
    })
  })

  it('does not handle stale errors from a replaced progressive source', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    h.mock(playbackService, 'restart')
    await playbackService.play(song)

    const previousMedia = playbackService.media
    const playNextMock = h.mock(playbackService, 'playNext')
    const logMock = h.mock(logger, 'error')
    playbackService.seekTo(50)
    dispatchLoadedMetadata(Number.POSITIVE_INFINITY)
    await h.tick()

    h.setReadOnlyProperty(previousMedia, 'error', { code: 4 })
    previousMedia.dispatchEvent(new Event('error'))

    expect(playNextMock).not.toHaveBeenCalled()
    expect(logMock).not.toHaveBeenCalled()

    playbackService.media.dispatchEvent(new Event('error'))
    expect(playNextMock).toHaveBeenCalledTimes(1)
  })

  it('detaches progressive source errors when playback stops', async () => {
    enableProgressivePlayback()
    const song = h.factory('song').make({ requires_transcoding: true, length: 400 })
    h.mock(playbackService, 'restart')
    await playbackService.play(song)

    const playMock = h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    playMock.mockClear()
    playbackService.seekTo(50)
    const previousMedia = playbackService.media
    const playNextMock = h.mock(playbackService, 'playNext')

    expect(playMock).toHaveBeenCalledTimes(1)

    await playbackService.stop()
    h.setReadOnlyProperty(previousMedia, 'duration', 400)
    previousMedia.dispatchEvent(new Event('loadedmetadata'))
    previousMedia.dispatchEvent(new Event('error'))

    expect(playNextMock).not.toHaveBeenCalled()
    expect(playMock).toHaveBeenCalledTimes(1)
    expect(playbackService.media.src).toBe('')
  })

  it('keeps outgoing progressive state canonical until the ready crossfade is promoted', async () => {
    enableProgressivePlayback()
    const outgoingSong = h.factory('song').make({ requires_transcoding: true, length: 400 })
    const incomingSong = h.factory('song').make({ requires_transcoding: true, length: 300 })
    h.mock(playbackService, 'restart')
    await playbackService.play(outgoingSong)
    queueStore.state.playables = reactive([outgoingSong, incomingSong])
    h.setReadOnlyProperty(queueStore, 'next', incomingSong)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 390)
    h.setReadOnlyProperty(playbackService.media, 'duration', Number.POSITIVE_INFINITY)
    preferences.temporary.crossfade_duration = 10
    h.mock(playbackService, 'registerPlay')
    h.mock(playbackService, 'preload')
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    h.mock(window, 'requestAnimationFrame', 1)
    h.mock(window, 'cancelAnimationFrame')
    const setVolumeMock = h.mock(playbackService, 'setVolume')

    playbackService.media.dispatchEvent(new Event('timeupdate'))
    await h.tick()
    setVolumeMock.mockClear()
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 391)
    playbackService.media.dispatchEvent(new Event('timeupdate'))

    expect(queueStore.current?.id).toBe(outgoingSong.id)
    expect(playbackService.duration).toBe(400)
    expect(setVolumeMock).toHaveBeenCalledWith(expect.any(Number))
    expect(setVolumeMock.mock.calls[0][0]).toBeLessThan(7)

    const incomingMedia = crossfadeService.state!.incomingAudio
    playbackService.media.dispatchEvent(new Event('ended'))

    await vi.waitFor(() => expect(playbackService.media).toBe(incomingMedia))

    expect(queueStore.current?.id).toBe(incomingSong.id)
    expect(playbackService.duration).toBe(300)
  })

  it('keeps the outgoing song current when crossfade playback is rejected', async () => {
    const outgoingSong = setCurrentSong(h.factory('song').make({ length: 100, playback_state: 'Playing' }))
    const incomingSong = h.factory('song').make({ length: 100 })
    queueStore.state.playables = reactive([outgoingSong, incomingSong])
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 95)
    h.setReadOnlyProperty(playbackService.media, 'duration', 100)
    preferences.temporary.crossfade_duration = 5
    h.mock(playbackService, 'preload')
    const registerPlayMock = h.mock(playbackService, 'registerPlay')
    h.mock(HTMLMediaElement.prototype, 'play').mockRejectedValueOnce(new Error('Playback rejected'))

    playbackService.media.dispatchEvent(new Event('timeupdate'))
    await h.tick()

    expect(queueStore.current?.id).toBe(outgoingSong.id)
    expect(outgoingSong.playback_state).toBe('Playing')
    expect(incomingSong.playback_state).toBe('Stopped')
    expect(registerPlayMock).not.toHaveBeenCalledWith(incomingSong)
    expect(queueStore.state.playables.map(playable => playable.id)).toEqual([outgoingSong.id, incomingSong.id])
  })

  it('keeps pause and resume attached to the outgoing song during an active crossfade', async () => {
    const outgoingSong = setCurrentSong(h.factory('song').make({ length: 100, playback_state: 'Playing' }))
    const incomingSong = h.factory('song').make({ length: 100 })
    queueStore.state.playables = reactive([outgoingSong, incomingSong])
    h.setReadOnlyProperty(queueStore, 'next', incomingSong)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 95)
    h.setReadOnlyProperty(playbackService.media, 'duration', 100)
    preferences.temporary.crossfade_duration = 5
    h.mock(playbackService, 'preload')
    h.mock(playbackService, 'registerPlay')
    h.mock(playbackService, 'showNotification')
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    h.mock(window, 'requestAnimationFrame', 1)
    const outgoingMedia = playbackService.media

    playbackService.media.dispatchEvent(new Event('timeupdate'))
    await vi.waitFor(() => expect(crossfadeService.active).toBe(true))

    expect(queueStore.current?.id).toBe(outgoingSong.id)
    expect(playbackService.media).toBe(outgoingMedia)

    const incomingPauseMock = h.mock(crossfadeService.state!.incomingAudio, 'pause')
    const outgoingPauseMock = h.mock(outgoingMedia, 'pause')

    await playbackService.pause()

    expect(incomingPauseMock).toHaveBeenCalled()
    expect(outgoingPauseMock).toHaveBeenCalled()
    expect(queueStore.current?.id).toBe(outgoingSong.id)
    expect(outgoingSong.playback_state).toBe('Paused')
    expect(crossfadeService.inProgress).toBe(false)

    await playbackService.resume()

    expect(queueStore.current?.id).toBe(outgoingSong.id)
    expect(outgoingSong.playback_state).toBe('Playing')
    expect(playbackService.media).toBe(outgoingMedia)
  })

  it('seeks the outgoing media and cancels an active crossfade', async () => {
    const outgoingSong = setCurrentSong(h.factory('song').make({ length: 100, playback_state: 'Playing' }))
    const incomingSong = h.factory('song').make({ length: 100 })
    queueStore.state.playables = reactive([outgoingSong, incomingSong])
    h.setReadOnlyProperty(queueStore, 'next', incomingSong)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 95)
    h.setReadOnlyProperty(playbackService.media, 'duration', 100)
    preferences.temporary.crossfade_duration = 5
    h.mock(playbackService, 'preload')
    h.mock(playbackService, 'registerPlay')
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    h.mock(window, 'requestAnimationFrame', 1)
    const outgoingMedia = playbackService.media

    playbackService.media.dispatchEvent(new Event('timeupdate'))
    await vi.waitFor(() => expect(crossfadeService.active).toBe(true))
    const incomingMedia = crossfadeService.state!.incomingAudio

    playbackService.seekTo(40)

    expect(crossfadeService.inProgress).toBe(false)
    expect(queueStore.current?.id).toBe(outgoingSong.id)
    expect(playbackService.media).toBe(outgoingMedia)
    expect(outgoingMedia.currentTime).toBe(40)
    expect(incomingMedia.src).toBe('')
  })

  it('does not launch another progressive preload while a crossfade is starting', async () => {
    enableProgressivePlayback()
    const outgoingSong = setCurrentSong(h.factory('song').make({ length: 100, playback_state: 'Playing' }))
    const incomingSong = h.factory('song').make({ requires_transcoding: true, length: 100 })
    queueStore.state.playables = reactive([outgoingSong, incomingSong])
    h.setReadOnlyProperty(queueStore, 'next', incomingSong)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 95)
    h.setReadOnlyProperty(playbackService.media, 'duration', 100)
    preferences.temporary.crossfade_duration = 5
    const registerPlayMock = h.mock(playbackService, 'registerPlay')
    h.mock(playbackService, 'showNotification')
    h.mock(window, 'requestAnimationFrame', 1)
    const originalCreateElement = document.createElement.bind(document)
    const createdAudioElements: HTMLAudioElement[] = []
    let resolvePlay!: () => void

    h.mock(document, 'createElement', (tagName: string) => {
      const element = originalCreateElement(tagName)

      if (element instanceof HTMLAudioElement) {
        createdAudioElements.push(element)
      }

      return element
    })
    h.mock(HTMLMediaElement.prototype, 'play').mockReturnValueOnce(
      new Promise<void>(resolve => (resolvePlay = resolve)),
    )

    playbackService.preload(incomingSong)
    playbackService.media.dispatchEvent(new Event('timeupdate'))
    playbackService.media.dispatchEvent(new Event('timeupdate'))

    expect(createdAudioElements.filter(media => media.preload === 'auto')).toHaveLength(1)
    expect(queueStore.current?.id).toBe(outgoingSong.id)
    expect(registerPlayMock).not.toHaveBeenCalledWith(incomingSong)

    resolvePlay()
    await h.tick(2)

    playbackService.media.dispatchEvent(new Event('timeupdate'))

    expect(createdAudioElements.filter(media => media.preload === 'auto')).toHaveLength(1)
  })

  it('promotes the pending crossfade without requesting another progressive stream when the outgoing song ends', async () => {
    enableProgressivePlayback()
    const outgoingSong = setCurrentSong(h.factory('song').make({ length: 100, playback_state: 'Playing' }))
    const incomingSong = h.factory('song').make({ requires_transcoding: true, length: 100 })
    queueStore.state.playables = reactive([outgoingSong, incomingSong])
    h.setReadOnlyProperty(queueStore, 'next', incomingSong)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 95)
    h.setReadOnlyProperty(playbackService.media, 'duration', 100)
    preferences.temporary.crossfade_duration = 5
    h.mock(playbackService, 'registerPlay')
    h.mock(playbackService, 'showNotification')
    h.mock(window, 'requestAnimationFrame', 1)
    const outgoingMedia = playbackService.media
    const originalCreateElement = document.createElement.bind(document)
    const createdAudioElements: HTMLAudioElement[] = []
    let resolveCrossfadePlayback!: () => void

    h.mock(document, 'createElement', (tagName: string) => {
      const element = originalCreateElement(tagName)

      if (element instanceof HTMLAudioElement) {
        createdAudioElements.push(element)
      }

      return element
    })
    h.mock(HTMLMediaElement.prototype, 'play').mockReturnValueOnce(
      new Promise<void>(resolve => (resolveCrossfadePlayback = resolve)),
    )

    playbackService.preload(incomingSong)
    playbackService.media.dispatchEvent(new Event('timeupdate'))
    await h.tick()

    const incomingMedia = createdAudioElements.find(media => media.src.includes('progressive=1'))!

    expect(crossfadeService.inProgress).toBe(true)
    expect(crossfadeService.active).toBe(false)
    expect(queueStore.current?.id).toBe(outgoingSong.id)

    outgoingMedia.dispatchEvent(new Event('ended'))
    await h.tick(2)

    expect(queueStore.current?.id).toBe(outgoingSong.id)
    expect(playbackService.media).toBe(outgoingMedia)

    resolveCrossfadePlayback()

    await vi.waitFor(() => expect(playbackService.media).toBe(incomingMedia))

    expect(queueStore.current?.id).toBe(incomingSong.id)
    expect(createdAudioElements).toHaveLength(3)
    expect(crossfadeService.inProgress).toBe(false)
  })

  it('does not let a stale crossfade promotion overwrite a newer skip', async () => {
    const outgoingSong = setCurrentSong(h.factory('song').make({ length: 100, playback_state: 'Playing' }))
    const incomingSong = h.factory('song').make({ length: 100 })
    const skippedSong = h.factory('song').make({ length: 100 })
    queueStore.state.playables = reactive([outgoingSong, incomingSong, skippedSong])
    h.setReadOnlyProperty(queueStore, 'next', incomingSong)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 95)
    h.setReadOnlyProperty(playbackService.media, 'duration', 100)
    preferences.temporary.crossfade_duration = 5
    h.mock(playbackService, 'preload')
    h.mock(playbackService, 'registerPlay')
    h.mock(playbackService, 'restart')
    h.mock(playbackService, 'showNotification')
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    h.mock(window, 'requestAnimationFrame', 1)
    const outgoingMedia = playbackService.media

    outgoingMedia.dispatchEvent(new Event('timeupdate'))
    await vi.waitFor(() => expect(crossfadeService.active).toBe(true))
    const incomingMedia = crossfadeService.state!.incomingAudio

    outgoingMedia.dispatchEvent(new Event('ended'))
    await playbackService.play(skippedSong)
    await h.tick(2)

    expect(queueStore.current?.id).toBe(skippedSong.id)
    expect(playbackService.media).not.toBe(incomingMedia)
    expect(crossfadeService.inProgress).toBe(false)
  })

  it('restarts an explicit play when the owned crossfade fails during promotion', async () => {
    const outgoingSong = setCurrentSong(h.factory('song').make({ length: 100, playback_state: 'Playing' }))
    const incomingSong = h.factory('song').make({ length: 100 })
    queueStore.state.playables = reactive([outgoingSong, incomingSong])
    h.setReadOnlyProperty(queueStore, 'next', incomingSong)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 95)
    h.setReadOnlyProperty(playbackService.media, 'duration', 100)
    preferences.temporary.crossfade_duration = 5
    h.mock(playbackService, 'preload')
    h.mock(playbackService, 'registerPlay')
    const restartMock = h.mock(playbackService, 'restart')
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    h.mock(window, 'requestAnimationFrame', 1)

    playbackService.media.dispatchEvent(new Event('timeupdate'))
    await vi.waitFor(() => expect(crossfadeService.active).toBe(true))

    const incomingMedia = crossfadeService.state!.incomingAudio
    let resumeAudioContext!: () => void
    const audioContextReady = new Promise<void>(resolve => {
      resumeAudioContext = resolve
    })
    audioService.context = {
      resume: vi.fn().mockReturnValue(audioContextReady),
    } as unknown as AudioContext

    const explicitPlay = playbackService.play(incomingSong)
    incomingMedia.dispatchEvent(new Event('error'))
    resumeAudioContext()
    await explicitPlay

    expect(restartMock).toHaveBeenCalledTimes(1)
    expect(queueStore.current?.id).toBe(incomingSong.id)
    expect(crossfadeService.inProgress).toBe(false)
    expect(playbackService.media).not.toBe(incomingMedia)
  })

  it('promotes a ready crossfade when resuming the audio context is rejected', async () => {
    const outgoingSong = setCurrentSong(h.factory('song').make({ length: 100, playback_state: 'Playing' }))
    const incomingSong = h.factory('song').make({ length: 100 })
    queueStore.state.playables = reactive([outgoingSong, incomingSong])
    h.setReadOnlyProperty(queueStore, 'next', incomingSong)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 95)
    h.setReadOnlyProperty(playbackService.media, 'duration', 100)
    preferences.temporary.crossfade_duration = 5
    h.mock(playbackService, 'preload')
    h.mock(playbackService, 'registerPlay')
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    h.mock(window, 'requestAnimationFrame', 1)
    const warningMock = h.mock(logger, 'warn')

    playbackService.media.dispatchEvent(new Event('timeupdate'))
    await vi.waitFor(() => expect(crossfadeService.active).toBe(true))
    const incomingMedia = crossfadeService.state!.incomingAudio
    const resumeError = new Error('Audio context is unavailable')
    audioService.context = {
      resume: vi.fn().mockRejectedValue(resumeError),
    } as unknown as AudioContext

    await expect(playbackService.play(incomingSong)).resolves.toBeUndefined()

    expect(warningMock).toHaveBeenCalledWith('Failed to resume the audio context:', resumeError)
    expect(queueStore.current?.id).toBe(incomingSong.id)
    expect(playbackService.media).toBe(incomingMedia)
    expect(crossfadeService.inProgress).toBe(false)
  })

  it('restores outgoing volume when ready incoming playback fails', async () => {
    const outgoingSong = setCurrentSong(h.factory('song').make({ length: 100, playback_state: 'Playing' }))
    const incomingSong = h.factory('song').make({ length: 100 })
    queueStore.state.playables = reactive([outgoingSong, incomingSong])
    h.setReadOnlyProperty(queueStore, 'next', incomingSong)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 98)
    h.setReadOnlyProperty(playbackService.media, 'duration', 100)
    preferences.temporary.crossfade_duration = 5
    h.mock(playbackService, 'preload')
    h.mock(playbackService, 'registerPlay')
    h.mock(volumeManager, 'get').mockReturnValue(7)
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    h.mock(window, 'requestAnimationFrame', 1)
    const setVolumeMock = h.mock(playbackService, 'setVolume')

    playbackService.media.dispatchEvent(new Event('timeupdate'))
    await vi.waitFor(() => expect(crossfadeService.active).toBe(true))
    playbackService.media.dispatchEvent(new Event('timeupdate'))

    expect(setVolumeMock).toHaveBeenLastCalledWith(expect.any(Number))
    expect(setVolumeMock.mock.calls.at(-1)![0]).toBeLessThan(7)

    crossfadeService.state!.incomingAudio.dispatchEvent(new Event('error'))

    expect(setVolumeMock).toHaveBeenLastCalledWith(7)
    expect(queueStore.current?.id).toBe(outgoingSong.id)
    expect(crossfadeService.failed).toBe(true)
  })

  it.each(['error', 'ended'])('falls back when incoming playback emits %s before promotion', async eventName => {
    const outgoingSong = setCurrentSong(h.factory('song').make({ length: 100, playback_state: 'Playing' }))
    const incomingSong = h.factory('song').make({ length: 100 })
    queueStore.state.playables = reactive([outgoingSong, incomingSong])
    h.setReadOnlyProperty(queueStore, 'next', incomingSong)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 95)
    h.setReadOnlyProperty(playbackService.media, 'duration', 100)
    preferences.temporary.crossfade_duration = 5
    h.mock(playbackService, 'preload')
    h.mock(playbackService, 'registerPlay')
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    h.mock(window, 'requestAnimationFrame', 1)
    const playNextMock = h.mock(playbackService, 'playNext')

    playbackService.media.dispatchEvent(new Event('timeupdate'))
    await vi.waitFor(() => expect(crossfadeService.active).toBe(true))

    crossfadeService.state!.incomingAudio.dispatchEvent(new Event(eventName))
    playbackService.media.dispatchEvent(new Event('ended'))

    await vi.waitFor(() => expect(playNextMock).toHaveBeenCalledTimes(1))
    expect(queueStore.current?.id).toBe(outgoingSong.id)
  })

  it('falls back only once when outgoing playback ends before crossfade readiness times out', async () => {
    vi.useFakeTimers()
    const outgoingSong = setCurrentSong(h.factory('song').make({ length: 100, playback_state: 'Playing' }))
    const incomingSong = h.factory('song').make({ length: 100 })
    queueStore.state.playables = reactive([outgoingSong, incomingSong])
    h.setReadOnlyProperty(queueStore, 'next', incomingSong)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 95)
    h.setReadOnlyProperty(playbackService.media, 'duration', 100)
    preferences.temporary.crossfade_duration = 5
    h.mock(playbackService, 'preload')
    h.mock(playbackService, 'registerPlay')
    h.mock(HTMLMediaElement.prototype, 'play').mockReturnValueOnce(new Promise<void>(() => undefined))
    const playNextMock = h.mock(playbackService, 'playNext')

    playbackService.media.dispatchEvent(new Event('timeupdate'))
    playbackService.media.dispatchEvent(new Event('ended'))
    playbackService.media.dispatchEvent(new Event('ended'))
    await vi.runAllTimersAsync()
    await h.tick(2)

    expect(playNextMock).toHaveBeenCalledTimes(1)
  })

  it('reloads an ended zero-offset progressive source for repeat-one playback', async () => {
    enableProgressivePlayback()
    const song = setCurrentSong(h.factory('song').make({ requires_transcoding: true, length: 400 }))
    h.mock(playbackService, 'showNotification')
    h.mock(HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    playbackService.media.src = 'http://test/previous'
    await playbackService.play(song)

    const endedMedia = playbackService.media
    h.setReadOnlyProperty(endedMedia, 'ended', true)
    await playbackService.restart()

    expect(playbackService.media).not.toBe(endedMedia)
    expect(playbackService.media.src).toContain('progressive=1')
    expect(playbackService.media.src).not.toContain('time=')
  })

  it('stops listening to media event after deactivation', () => {
    playbackService.deactivate()

    const logMock = h.mock(logger, 'error')
    const playNextMock = h.mock(playbackService, 'playNext')

    playbackService.media.dispatchEvent(new Event('error'))

    expect(playNextMock).not.toHaveBeenCalled()
    expect(logMock).not.toHaveBeenCalled()
  })
})
