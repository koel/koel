import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { radioStationStore } from '@/stores/radioStationStore'
import { socketService } from '@/services/socketService'
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
    playbackService.activate(document.querySelector<HTMLMediaElement>('#audio-player')!)
    expect(playbackService.media).toBe(media)
  })

  it('plays a radio station', async () => {
    const currentStation = h.factory('radio-station')
    currentStation.playback_state = 'Playing'
    const toBePlayedStation = h.factory('radio-station')
    toBePlayedStation.playback_state = 'Stopped'

    radioStationStore.state.stations = [currentStation, toBePlayedStation]

    const playMock = h.mock(playbackService.media, 'play')
    const broadcastMock = h.mock(socketService, 'broadcast')
    h.mock(radioStationStore, 'getSourceUrl', 'https://station.com/stream.mp3')

    const startPollingMock = h.mock(radioStationStore, 'startPolling')

    await playbackService.play(toBePlayedStation)

    expect(playMock).toHaveBeenCalled()
    expect(playbackService.media.src).toBe('https://station.com/stream.mp3')
    expect(currentStation.playback_state).toBe('Stopped')
    expect(toBePlayedStation.playback_state).toBe('Playing')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', toBePlayedStation)
    expect(startPollingMock).toHaveBeenCalledWith(toBePlayedStation)
  })

  it('pauses a radio station playback', async () => {
    const currentStation = h.factory('radio-station')
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
})
