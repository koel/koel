import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { radioStationStore } from '@/stores/radioStationStore'
import { socketService } from '@/services/socketService'
import { audioService } from '@/services/audioService'
import { playbackService } from '@/services/RadioPlaybackService'

describe('playbackService', () => {
  const h = createHarness({
    beforeEach: () => {
      h.createAudioPlayer()
      playbackService.activate(document.querySelector<HTMLMediaElement>('#audio-player')!)
    },
  })

  it('only initializes once', () => {
    const media = playbackService.media
    playbackService.activate(document.createElement('audio'))
    expect(playbackService.media).toBe(media)
  })

  it('plays a radio station', async () => {
    const currentStation = h.factory('radio-station').make()
    currentStation.playback_state = 'Playing'
    const toBePlayedStation = h.factory('radio-station').make()
    toBePlayedStation.playback_state = 'Stopped'

    radioStationStore.state.stations = [currentStation, toBePlayedStation]

    const playMock = h.mock(playbackService.media, 'play')
    const broadcastMock = h.mock(socketService, 'broadcast')
    h.mock(radioStationStore, 'resolveSourceUrl').mockResolvedValue('https://station.com/stream.mp3')

    const startPollingMock = h.mock(radioStationStore, 'startPolling')
    const routeElementMock = h.mock(audioService, 'routeElement')

    await playbackService.play(toBePlayedStation)

    expect(routeElementMock).toHaveBeenCalledWith(playbackService.media)
    expect(playMock).toHaveBeenCalled()
    expect(playbackService.media.src).toBe('https://station.com/stream.mp3')
    expect(currentStation.playback_state).toBe('Stopped')
    expect(toBePlayedStation.playback_state).toBe('Playing')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', toBePlayedStation)
    expect(startPollingMock).toHaveBeenCalledWith(toBePlayedStation)
  })

  it('does not start a station that was stopped while it was loading', async () => {
    const station = h.factory('radio-station').make({ playback_state: 'Stopped' })
    radioStationStore.state.stations = [station]
    let resolveSourceUrl: (url: string) => void = () => {}
    h.mock(radioStationStore, 'resolveSourceUrl').mockReturnValue(new Promise(resolve => (resolveSourceUrl = resolve)))
    const playMock = h.mock(playbackService.media, 'play')
    h.mock(radioStationStore, 'stopPolling')

    const playing = playbackService.play(station)
    await playbackService.stop()
    resolveSourceUrl('https://station.com/stream.mp3')
    await playing

    expect(playMock).not.toHaveBeenCalled()
    expect(station.playback_state).toBe('Paused')
  })

  it('takes over the media keys whenever it becomes active', async () => {
    const handlers = new Map<string, () => void>()
    h.setReadOnlyProperty(navigator, 'mediaSession', {
      setActionHandler: (action: string, handler: () => void) => handlers.set(action, handler),
    })
    const pauseMock = h.mock(playbackService, 'pause')

    playbackService.activate(playbackService.media)
    handlers.get('pause')!()

    expect(pauseMock).toHaveBeenCalled()
  })

  it('pauses a radio station playback', async () => {
    const currentStation = h.factory('radio-station').make()
    currentStation.playback_state = 'Playing'
    radioStationStore.state.stations = [currentStation]

    const pauseMock = h.mock(playbackService.media, 'pause')
    const broadcastMock = h.mock(socketService, 'broadcast')
    const stopPollingMock = h.mock(radioStationStore, 'stopPolling')
    await playbackService.stop()

    expect(stopPollingMock).toHaveBeenCalled()
    expect(pauseMock).toHaveBeenCalled()
    expect(playbackService.media.src).toBe('')
    expect(currentStation.playback_state).toBe('Paused')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', currentStation)
  })

  it('stops showing a station as playing when its stream URL cannot be fetched', async () => {
    const station = h.factory('radio-station').make({ playback_state: 'Stopped' })
    h.mock(radioStationStore, 'resolveSourceUrl').mockRejectedValue(new Error('offline'))

    await expect(playbackService.play(station)).rejects.toThrow('offline')

    expect(station.playback_state).toBe('Stopped')
  })
})
