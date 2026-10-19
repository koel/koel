import { nextTick, reactive } from 'vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import * as lodash from 'lodash-es'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { audioService } from '@/services/audioService'

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
import { playbackService } from '@/services/QueuePlaybackService'
import { crossfadeService } from '@/services/crossfadeService'
import { volumeManager } from '@/services/volumeManager'
import { useBranding } from '@/composables/useBranding'
import { usePageTitle } from '@/composables/usePageTitle'

describe('playbackService', () => {
  const h = createHarness({
    beforeEach: () => {
      playableStore.vault.clear()
      h.createAudioPlayer()
      playbackService.activate(document.querySelector<HTMLMediaElement>('#audio-player')!)
    },
  })

  const setCurrentSong = (song?: Playable) => {
    const playbackState = song?.playback_state ?? 'Playing'
    const [synced] = playableStore.syncWithVault(song || h.factory('song').make())
    synced.playback_state = playbackState
    queueStore.state.playables = reactive([synced])
    return synced
  }

  it('only initializes once', () => {
    const media = playbackService.media
    playbackService.activate(document.createElement('audio'))
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

  it('saves the playback position once per five-second mark', () => {
    const song = setCurrentSong()
    const mediaElement = playbackService.media
    h.setReadOnlyProperty(mediaElement, 'currentTime', 10)
    h.setReadOnlyProperty(mediaElement, 'duration', 400)
    const putMock = h.mock(http, 'put')

    mediaElement.dispatchEvent(new Event('timeupdate'))
    mediaElement.dispatchEvent(new Event('timeupdate'))
    mediaElement.dispatchEvent(new Event('timeupdate'))

    expect(putMock).toHaveBeenCalledOnce()
    expect(putMock).toHaveBeenCalledWith('queue/playback-status', { song: song.id, position: 10 })
  })

  it('saves the playback position again after the playable restarts', async () => {
    const song = setCurrentSong()
    const mediaElement = playbackService.media
    const putMock = h.mock(http, 'put')
    h.mock(window.HTMLMediaElement.prototype, 'play')
    h.setReadOnlyProperty(mediaElement, 'duration', 400)
    h.setReadOnlyProperty(mediaElement, 'currentTime', 5)
    mediaElement.dispatchEvent(new Event('timeupdate'))

    await playbackService.restart()
    h.setReadOnlyProperty(mediaElement, 'currentTime', 5)
    mediaElement.dispatchEvent(new Event('timeupdate'))

    await vi.waitFor(() =>
      expect(putMock).toHaveBeenLastCalledWith('queue/playback-status', { song: song.id, position: 5 }),
    )
  })

  it('saves the playback position on pause', () => {
    const song = setCurrentSong()
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 42)
    const putMock = h.mock(http, 'put')

    playbackService.pause()

    expect(putMock).toHaveBeenCalledWith('queue/playback-status', { song: song.id, position: 42 })
  })

  it('plays next playable if current playable is errored', () => {
    const logMock = h.mock(logger, 'error')
    const playNextMock = h.mock(playbackService, 'playNext')

    const errorEvent = new Event('error')
    playbackService.media.dispatchEvent(errorEvent)

    expect(playNextMock).toHaveBeenCalled()
    expect(logMock).toHaveBeenCalledWith(errorEvent)
  })

  it('routes its own element through the audio graph before playing a song', async () => {
    const song = h.factory('song').make()
    h.mock(playableStore, 'resolveSourceUrl').mockResolvedValue('http://test/play/song')
    h.mock(playbackService, 'restart')
    const routeElementMock = h.mock(audioService, 'routeElement')

    await playbackService.play(song)

    expect(routeElementMock).toHaveBeenCalledWith(playbackService.media)
    playbackService.media.removeAttribute('src')
  })

  it('counts a song again when it was paused while loading', async () => {
    const song = h.factory('song').make({ play_count_registered: true })
    let resolveSourceUrl: (url: string) => void = () => {}
    h.mock(playableStore, 'resolveSourceUrl').mockReturnValue(new Promise(resolve => (resolveSourceUrl = resolve)))

    const playing = playbackService.play(song)
    song.playback_state = 'Paused'
    resolveSourceUrl('http://test/play/song')
    await playing

    expect(song.play_count_registered).toBe(false)
    playbackService.media.removeAttribute('src')
  })

  it('announces the first song as up next near the end of the last one with Repeat All', () => {
    preferences.temporary.repeat_mode = 'REPEAT_ALL'
    const [first, last] = playableStore.syncWithVault(h.factory('song').make(2))
    last.playback_state = 'Playing'
    last.play_count_registered = true
    queueStore.state.playables = reactive([first, last])
    h.mock(playbackService, 'preload')
    const emitMock = h.mock(eventBus, 'emit')

    h.setReadOnlyProperty(playbackService.media, 'duration', 300)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 290)
    playbackService.media.dispatchEvent(new Event('timeupdate'))

    expect(emitMock).toHaveBeenCalledWith('UP_NEXT', first)
    preferences.temporary.repeat_mode = 'NO_REPEAT'
  })

  it('clears up next when no song follows anymore', () => {
    preferences.temporary.repeat_mode = 'REPEAT_ALL'
    const [first, last] = playableStore.syncWithVault(h.factory('song').make(2))
    last.playback_state = 'Playing'
    last.play_count_registered = true
    queueStore.state.playables = reactive([first, last])
    h.mock(playbackService, 'preload')
    const emitMock = h.mock(eventBus, 'emit')

    h.setReadOnlyProperty(playbackService.media, 'duration', 300)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', 290)
    playbackService.media.dispatchEvent(new Event('timeupdate'))

    preferences.temporary.repeat_mode = 'NO_REPEAT'
    playbackService.media.dispatchEvent(new Event('timeupdate'))

    expect(emitMock).toHaveBeenLastCalledWith('UP_NEXT', null)
  })

  it('reloads an expired source and resumes where it stopped', async () => {
    const song = h.factory('song').make({ playback_state: 'Playing' })
    queueStore.state.playables = reactive([song])

    const expiredAt = Math.floor(Date.now() / 1000) - 60
    playbackService.media.src = `http://test/play/${song.id}?expires=${expiredAt}&signature=old`
    const currentTimeSpy = vi.spyOn(playbackService.media, 'currentTime', 'get').mockReturnValue(42)

    const forgetMock = h.mock(playableStore, 'forgetResolvedSourceUrl')
    h.mock(playableStore, 'resolveSourceUrl').mockResolvedValue(`http://test/play/${song.id}?signature=new`)
    const seekMock = h.mock(playbackService, 'seekTo')
    const playMock = h.mock(window.HTMLMediaElement.prototype, 'play')
    const playNextMock = h.mock(playbackService, 'playNext')

    playbackService.media.dispatchEvent(new Event('error'))

    await vi.waitFor(() => expect(playMock).toHaveBeenCalled())
    expect(forgetMock).toHaveBeenCalledWith(song)
    expect(playbackService.media.src).toBe(`http://test/play/${song.id}?signature=new`)
    expect(seekMock).toHaveBeenCalledWith(42)
    expect(playNextMock).not.toHaveBeenCalled()

    currentTimeSpy.mockRestore()
    playbackService.media.removeAttribute('src')
  })

  it('moves on instead of reloading an expired source again within a minute', async () => {
    const song = h.factory('song').make({ playback_state: 'Playing' })
    queueStore.state.playables = reactive([song])

    const expiredAt = Math.floor(Date.now() / 1000) - 60
    playbackService.media.src = `http://test/play/${song.id}?expires=${expiredAt}&signature=old`

    const resolveMock = h.mock(playableStore, 'resolveSourceUrl').mockResolvedValue(playbackService.media.src)
    h.mock(window.HTMLMediaElement.prototype, 'play')
    h.mock(playbackService, 'seekTo')
    const playNextMock = h.mock(playbackService, 'playNext')

    playbackService.media.dispatchEvent(new Event('error'))
    await vi.waitFor(() => expect(resolveMock).toHaveBeenCalled())

    playbackService.media.dispatchEvent(new Event('error'))
    await vi.waitFor(() => expect(playNextMock).toHaveBeenCalled())

    expect(resolveMock).toHaveBeenCalledTimes(1)

    playbackService.media.removeAttribute('src')
  })

  it('reloads an expired source again after a minute of playback', async () => {
    const song = h.factory('song').make({ playback_state: 'Playing' })
    queueStore.state.playables = reactive([song])

    const expiredUrl = () => `http://test/play/${song.id}?expires=${Math.floor(Date.now() / 1000) - 60}&signature=old`

    playbackService.media.src = expiredUrl()

    const resolveMock = h.mock(playableStore, 'resolveSourceUrl').mockImplementation(async () => expiredUrl())
    h.mock(window.HTMLMediaElement.prototype, 'play')
    h.mock(playbackService, 'seekTo')
    const playNextMock = h.mock(playbackService, 'playNext')

    playbackService.media.dispatchEvent(new Event('error'))
    await vi.waitFor(() => expect(resolveMock).toHaveBeenCalledTimes(1))

    vi.setSystemTime(Date.now() + 30 * 60 * 1000)
    playbackService.media.src = expiredUrl()
    playbackService.media.dispatchEvent(new Event('error'))
    await vi.waitFor(() => expect(resolveMock).toHaveBeenCalledTimes(2))

    expect(playNextMock).not.toHaveBeenCalled()

    vi.useRealTimers()
    playbackService.media.removeAttribute('src')
  })

  const connectToLastfm = () => {
    commonStore.state.uses_last_fm = true
    userStore.state.current.preferences.lastfm_session_key = 'foo'
    userStore.state.current.preferences.listenbrainz_token = undefined
  }

  const connectToListenBrainz = () => {
    commonStore.state.uses_last_fm = false
    userStore.state.current.preferences.lastfm_session_key = undefined
    userStore.state.current.preferences.listenbrainz_token = 'token'
  }

  const disconnectScrobblers = () => {
    commonStore.state.uses_last_fm = false
    userStore.state.current.preferences.lastfm_session_key = undefined
    userStore.state.current.preferences.listenbrainz_token = undefined
  }

  const playTo = (currentTime: number, duration: number, song?: Playable) => {
    setCurrentSong(song)
    h.setReadOnlyProperty(playbackService.media, 'currentTime', currentTime)
    h.setReadOnlyProperty(playbackService.media, 'duration', duration)

    const scrobbleMock = h.mock(playableStore, 'scrobble')
    h.mock(playbackService, 'registerPlay')
    h.mock(http, 'put')

    playbackService.media.dispatchEvent(new Event('timeupdate'))

    return scrobbleMock
  }

  it.each<[string, number, number, number]>([
    ['half of a short track has been played', 100, 200, 1],
    ['just before half of a short track', 99, 200, 0],
    ['four minutes of a long track have been played', 240, 600, 1],
    ['just before four minutes of a long track', 239, 600, 0],
    ['the track is too short to ever count', 20, 20, 0],
  ])('scrobbles when %s', (_, currentTime, duration, calls) => {
    connectToLastfm()

    expect(playTo(currentTime, duration)).toHaveBeenCalledTimes(calls)
  })

  it('scrobbles when connected to ListenBrainz only', () => {
    connectToListenBrainz()

    expect(playTo(100, 200)).toHaveBeenCalledOnce()
  })

  it('does not scrobble without a connected service', () => {
    disconnectScrobblers()

    expect(playTo(100, 200)).not.toHaveBeenCalled()
  })

  it('does not scrobble the same playable twice', () => {
    connectToLastfm()
    const song = h.factory('song').make({ playback_state: 'Playing', scrobble_registered: true })

    expect(playTo(100, 200, song)).not.toHaveBeenCalled()
  })

  it('does not scrobble when the playable ends', () => {
    connectToLastfm()
    setCurrentSong()

    const scrobbleMock = h.mock(playableStore, 'scrobble')
    playbackService.media.dispatchEvent(new Event('ended'))

    expect(scrobbleMock).not.toHaveBeenCalled()
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

  it('fetches the next source URL instead of crossfading without one', () => {
    setCurrentSong()
    h.mock(playbackService, 'registerPlay')
    h.setReadOnlyProperty(queueStore, 'next', h.factory('song').make({ preloaded: true }))
    preferences.temporary.crossfade_duration = 5

    const mediaElement = playbackService.media
    h.setReadOnlyProperty(mediaElement, 'currentTime', 296)
    h.setReadOnlyProperty(mediaElement, 'duration', 300)

    h.mock(playableStore, 'getResolvedSourceUrl').mockReturnValue(undefined)
    const resolveMock = h.mock(playableStore, 'resolveSourceUrl').mockResolvedValue('http://test/play/signed')
    const startMock = h.mock(crossfadeService, 'start')
    h.mock(http, 'put')

    mediaElement.dispatchEvent(new Event('timeupdate'))

    expect(resolveMock).toHaveBeenCalledWith(queueStore.next)
    expect(startMock).not.toHaveBeenCalled()

    preferences.temporary.crossfade_duration = 0
  })

  const startCrossfadeAtFiveSeconds = () => {
    const [current, next] = playableStore.syncWithVault(h.factory('song').make(2))
    current.playback_state = 'Playing'
    queueStore.state.playables = reactive([current, next])
    h.setReadOnlyProperty(queueStore, 'next', next)

    h.mock(playbackService, 'registerPlay')
    h.mock(playbackService, 'showNotification')
    h.mock(http, 'put')
    h.mock(window.HTMLMediaElement.prototype, 'play').mockResolvedValue(undefined)
    h.mock(playableStore, 'getResolvedSourceUrl').mockReturnValue('http://test/play/next')
    h.mock(playbackService, 'preload')
    preferences.temporary.crossfade_duration = 5
    volumeManager.volume.value = 8

    const outgoingMedia = playbackService.media
    h.setReadOnlyProperty(outgoingMedia, 'currentTime', 297.5)
    h.setReadOnlyProperty(outgoingMedia, 'duration', 300)
    outgoingMedia.dispatchEvent(new Event('timeupdate'))

    return { outgoingMedia, incomingMedia: crossfadeService.state!.incomingAudio }
  }

  const endCrossfadeTest = (outgoingMedia: HTMLMediaElement) => {
    crossfadeService.cancel()
    playbackService.swapMediaElement(outgoingMedia)
    preferences.temporary.crossfade_duration = 0
    volumeManager.volume.value = 0
  }

  it('fades the next song in on time updates alone during a crossfade', () => {
    const { outgoingMedia, incomingMedia } = startCrossfadeAtFiveSeconds()

    expect(outgoingMedia.volume).toBeCloseTo(0.4)
    expect(incomingMedia.volume).toBeCloseTo(0.4)

    h.setReadOnlyProperty(outgoingMedia, 'currentTime', 299.5)
    outgoingMedia.dispatchEvent(new Event('timeupdate'))

    expect(outgoingMedia.volume).toBeCloseTo(0.08)
    expect(incomingMedia.volume).toBeCloseTo(0.72)

    endCrossfadeTest(outgoingMedia)
  })

  it('takes over the faded-in song without a volume jump', async () => {
    const { outgoingMedia, incomingMedia } = startCrossfadeAtFiveSeconds()

    h.setReadOnlyProperty(outgoingMedia, 'currentTime', 299.9)
    outgoingMedia.dispatchEvent(new Event('timeupdate'))
    const fadedInVolume = incomingMedia.volume

    outgoingMedia.dispatchEvent(new Event('ended'))
    await vi.waitFor(() => expect(playbackService.media).toBe(incomingMedia))

    expect(incomingMedia.volume - fadedInVolume).toBeLessThan(0.02)
    expect(crossfadeService.active).toBe(false)

    endCrossfadeTest(outgoingMedia)
  })

  it('counts a song reached by crossfade as played once', async () => {
    const { outgoingMedia, incomingMedia } = startCrossfadeAtFiveSeconds()
    const incomingSong = queueStore.current!
    incomingSong.play_count_registered = true
    const registerPlayMock = vi.mocked(playbackService.registerPlay)
    registerPlayMock.mockClear()

    outgoingMedia.dispatchEvent(new Event('ended'))
    await vi.waitFor(() => expect(playbackService.media).toBe(incomingMedia))

    h.setReadOnlyProperty(incomingMedia, 'duration', 300)
    h.setReadOnlyProperty(incomingMedia, 'currentTime', 150)
    incomingMedia.dispatchEvent(new Event('timeupdate'))

    expect(registerPlayMock).not.toHaveBeenCalled()

    endCrossfadeTest(outgoingMedia)
  })

  it('keeps fading during a crossfade into the last song in the queue', () => {
    const { outgoingMedia, incomingMedia } = startCrossfadeAtFiveSeconds()
    h.setReadOnlyProperty(queueStore, 'next', undefined)

    h.setReadOnlyProperty(outgoingMedia, 'currentTime', 299.5)
    outgoingMedia.dispatchEvent(new Event('timeupdate'))

    expect(outgoingMedia.volume).toBeCloseTo(0.08)
    expect(incomingMedia.volume).toBeCloseTo(0.72)

    endCrossfadeTest(outgoingMedia)
  })

  it('does not save the outgoing song time as the incoming song position during a crossfade', async () => {
    const { outgoingMedia } = startCrossfadeAtFiveSeconds()
    const incomingSong = crossfadeService.state!.playable
    const putMock = h.mock(http, 'put')

    h.setReadOnlyProperty(outgoingMedia, 'currentTime', 299)
    outgoingMedia.dispatchEvent(new Event('timeupdate'))
    h.setReadOnlyProperty(outgoingMedia, 'currentTime', 300)
    outgoingMedia.dispatchEvent(new Event('timeupdate'))
    await new Promise(resolve => setTimeout(resolve, 50))

    expect(putMock).not.toHaveBeenCalledWith(
      'queue/playback-status',
      expect.objectContaining({ song: incomingSong.id }),
    )

    endCrossfadeTest(outgoingMedia)
  })

  it('seeks within the song shown as playing when seeking during a crossfade', () => {
    const { outgoingMedia, incomingMedia } = startCrossfadeAtFiveSeconds()

    playbackService.seekTo(100)

    expect(crossfadeService.active).toBe(false)
    expect(playbackService.media).toBe(incomingMedia)
    expect(incomingMedia.currentTime).toBe(100)
    expect(incomingMedia.volume).toBeCloseTo(0.8)

    endCrossfadeTest(outgoingMedia)
  })

  it('pauses the song shown as playing when pausing during a crossfade', async () => {
    const { outgoingMedia, incomingMedia } = startCrossfadeAtFiveSeconds()
    const incomingSong = crossfadeService.state!.playable
    const pauseMock = h.mock(incomingMedia, 'pause')

    await playbackService.pause()

    expect(playbackService.media).toBe(incomingMedia)
    expect(pauseMock).toHaveBeenCalled()
    expect(queueStore.current?.id).toBe(incomingSong.id)

    endCrossfadeTest(outgoingMedia)
  })

  it('ends the crossfade when going back during one', async () => {
    const { outgoingMedia, incomingMedia } = startCrossfadeAtFiveSeconds()
    h.mock(playbackService, 'play')

    await playbackService.playPrev()

    expect(crossfadeService.active).toBe(false)
    expect(playbackService.media).toBe(incomingMedia)

    endCrossfadeTest(outgoingMedia)
  })

  it('registers play', () => {
    const recentlyPlayedStoreAddMock = h.mock(recentlyPlayedStore, 'add')
    const registerPlayMock = h.mock(playableStore, 'registerPlay')
    const song = h.factory('song').make()

    playbackService.registerPlay(song)

    expect(recentlyPlayedStoreAddMock).toHaveBeenCalledWith(song)
    expect(registerPlayMock).toHaveBeenCalledWith(song)
    expect(song.play_count_registered).toBe(true)
  })

  it('preloads a playable', async () => {
    const audioElement = {
      setAttribute: vi.fn(),
      load: vi.fn(),
    }

    const createElementMock = h.mock(document, 'createElement', audioElement)
    h.mock(playableStore, 'resolveSourceUrl').mockResolvedValue('/foo?signature=o5afd')
    const song = h.factory('song').make()

    await playbackService.preload(song)

    expect(createElementMock).toHaveBeenCalledWith('audio')
    expect(audioElement.setAttribute).toHaveBeenNthCalledWith(1, 'src', '/foo?signature=o5afd')
    expect(audioElement.setAttribute).toHaveBeenNthCalledWith(2, 'preload', 'auto')
    expect(audioElement.load).toHaveBeenCalled()
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

  it('plays the song after the playing one when that one is removed from the queue', async () => {
    const songs = playableStore.syncWithVault(h.factory('song').make(4))
    songs[1].playback_state = 'Playing'
    queueStore.state.playables = reactive(songs)
    const playMock = h.mock(playbackService, 'play')

    await playbackService.removeFromQueue([songs[1]])

    expect(playMock).toHaveBeenCalledWith(songs[2])
    expect(queueStore.all.map(({ id }) => id)).toEqual([songs[0].id, songs[2].id, songs[3].id])
  })

  it('stops when the removed playing song had nothing after it', async () => {
    preferences.temporary.repeat_mode = 'NO_REPEAT'
    const songs = playableStore.syncWithVault(h.factory('song').make(2))
    songs[1].playback_state = 'Playing'
    queueStore.state.playables = reactive(songs)
    const stopMock = h.mock(playbackService, 'stop')

    await playbackService.removeFromQueue([songs[1]])

    expect(stopMock).toHaveBeenCalled()
  })

  it('stops playback', () => {
    const currentSong = setCurrentSong()
    const pauseMock = h.mock(playbackService.media, 'pause')
    const broadcastMock = h.mock(socketService, 'broadcast')

    playbackService.stop()

    expect(currentSong.playback_state).toEqual('Stopped')
    expect(pauseMock).toHaveBeenCalled()
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_PLAYBACK_STOPPED')
    expect(usePageTitle().nowPlayingTitle.value).toBeNull()
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

    h.mock(playableStore, 'resolveSourceUrl').mockResolvedValue(`http://test/play/${song.id}?signature=s`)
    const playMock = h.mock(window.HTMLMediaElement.prototype, 'play')
    const broadcastMock = h.mock(socketService, 'broadcast')

    await playbackService.resume()

    expect(queueStore.current?.playback_state).toEqual('Playing')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', song)
    expect(playMock).toHaveBeenCalled()
  })

  it('gets a new source URL before resuming after a long pause', async () => {
    const song = setCurrentSong(h.factory('song').make({ playback_state: 'Paused' }))

    const expiredAt = Math.floor(Date.now() / 1000) - 60 * 60
    playbackService.media.src = `http://test/play/${song.id}?expires=${expiredAt}&signature=old`
    const currentTimeSpy = vi.spyOn(playbackService.media, 'currentTime', 'get').mockReturnValue(42)

    h.mock(playableStore, 'resolveSourceUrl').mockResolvedValue(`http://test/play/${song.id}?signature=new`)
    const seekMock = h.mock(playbackService, 'seekTo')
    const playMock = h.mock(window.HTMLMediaElement.prototype, 'play')

    await playbackService.resume()

    expect(playbackService.media.src).toBe(`http://test/play/${song.id}?signature=new`)
    expect(seekMock).toHaveBeenCalledWith(42)
    expect(playMock).toHaveBeenCalledTimes(1)
    expect(song.playback_state).toBe('Playing')

    currentTimeSpy.mockRestore()
    playbackService.media.removeAttribute('src')
  })

  it('stops the current audio as soon as another playable is requested', async () => {
    const resolveMock = h.mock(playableStore, 'resolveSourceUrl').mockReturnValue(new Promise(() => {}))
    const pauseMock = h.mock(playbackService.media, 'pause')

    playbackService.play(h.factory('song').make())

    await vi.waitFor(() => expect(resolveMock).toHaveBeenCalled())
    expect(pauseMock).toHaveBeenCalled()
  })

  it('ignores a slow source URL once a newer playable is requested', async () => {
    const [slowSong, fastSong] = h.factory('song').make(2)
    let resolveSlowUrl: (url: string) => void = () => {}

    h.mock(playableStore, 'resolveSourceUrl').mockImplementation(async (playable: Playable) =>
      playable.id === slowSong.id
        ? await new Promise<string>(resolve => (resolveSlowUrl = resolve))
        : `http://test/play/${fastSong.id}?signature=fast`,
    )

    h.mock(window.HTMLMediaElement.prototype, 'play')
    h.mock(playbackService, 'showNotification')

    const slowPlay = playbackService.play(slowSong)
    await playbackService.play(fastSong)
    resolveSlowUrl(`http://test/play/${slowSong.id}?signature=slow`)
    await slowPlay

    expect(playbackService.media.src).toBe(`http://test/play/${fastSong.id}?signature=fast`)

    playbackService.media.removeAttribute('src')
  })

  it('stops a playable whose source URL cannot be resolved', async () => {
    const song = h.factory('song').make()
    h.mock(playableStore, 'resolveSourceUrl').mockRejectedValue(new Error('offline'))
    const playMock = h.mock(window.HTMLMediaElement.prototype, 'play')
    h.mock(logger, 'error')

    await playbackService.play(song)

    expect(song.playback_state).toBe('Stopped')
    expect(playMock).not.toHaveBeenCalled()
  })

  it('does not resume a playable whose source URL cannot be resolved', async () => {
    setCurrentSong(h.factory('song').make({ playback_state: 'Paused' }))
    playbackService.media.removeAttribute('src')
    h.mock(playableStore, 'resolveSourceUrl').mockRejectedValue(new Error('offline'))
    const playMock = h.mock(window.HTMLMediaElement.prototype, 'play')
    h.mock(logger, 'error')

    await playbackService.resume()

    expect(playMock).not.toHaveBeenCalled()
  })

  it('does not start a playable paused while its source URL was loading', async () => {
    const song = h.factory('song').make()
    let resolveUrl: (url: string) => void = () => {}

    const resolveMock = h
      .mock(playableStore, 'resolveSourceUrl')
      .mockReturnValue(new Promise<string>(resolve => (resolveUrl = resolve)))

    const playMock = h.mock(window.HTMLMediaElement.prototype, 'play')
    h.mock(playbackService, 'showNotification')
    const seekMock = h.mock(playbackService, 'seekTo')

    const pendingPlay = playbackService.play(song, 42)
    await vi.waitFor(() => expect(resolveMock).toHaveBeenCalled())

    song.playback_state = 'Paused'
    resolveUrl(`http://test/play/${song.id}?signature=s`)
    await pendingPlay

    expect(playbackService.media.src).toBe(`http://test/play/${song.id}?signature=s`)
    expect(seekMock).toHaveBeenCalledWith(42)
    expect(playMock).not.toHaveBeenCalled()

    playbackService.media.removeAttribute('src')
  })

  it('fetches the next playable source URL as soon as a playable starts', async () => {
    const [song, nextSong] = h.factory('song').make(2)
    h.setReadOnlyProperty(playbackService, 'next', nextSong)

    const resolveMock = h.mock(playableStore, 'resolveSourceUrl').mockResolvedValue('http://test/play/signed')
    h.mock(window.HTMLMediaElement.prototype, 'play')
    h.mock(playbackService, 'showNotification')

    await playbackService.play(song)

    expect(resolveMock).toHaveBeenLastCalledWith(nextSong)

    playbackService.media.removeAttribute('src')
  })

  it('shows the playing song in the page title', async () => {
    const song = h.factory('song').make()
    h.mock(window.HTMLMediaElement.prototype, 'play')
    h.mock(playbackService, 'showNotification')

    await playbackService.play(song)

    expect(usePageTitle().nowPlayingTitle.value).toBe(song.title)
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

  it.each<[string, string | null]>([
    ['the playable cover', 'https://test/cover.jpg'],
    ['the branding cover', null],
  ])('sets the media session artwork to %s', (_, albumCover) => {
    const song = h.factory('song').make({ album_cover: albumCover! })
    preferences.temporary.show_now_playing_notification = false

    playbackService.showNotification(song)

    const { artwork } = navigator.mediaSession.metadata!

    expect(artwork).toHaveLength(8)
    artwork!.forEach(image => expect(image.src).toBe(albumCover ?? useBranding().cover))
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
