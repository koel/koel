import plyr from 'plyr'
import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { radioStationStore } from '@/stores/radioStationStore'
import { socketService } from '@/services/socketService'
import { playbackService } from '@/services/RadioPlaybackService'

describe('playbackService', () => {
  const h = createHarness({
    beforeEach: () => {
      h.createAudioPlayer()
      playbackService.activate(document.querySelector('.plyr')!)
    },
  })

  it('only initializes once', () => {
    const spy = vi.spyOn(plyr, 'setup')

    playbackService.activate(document.querySelector('.plyr')!)
    expect(spy).not.toHaveBeenCalled()
  })

  it('plays a radio station', async () => {
    const currentStation = h.factory('radio-station')
    currentStation.playback_state = 'Playing'
    const toBePlayedStation = h.factory('radio-station')
    toBePlayedStation.playback_state = 'Stopped'

    radioStationStore.state.stations = [currentStation, toBePlayedStation]

    const playMock = h.mock(playbackService.player.media, 'play')
    const broadcastMock = h.mock(socketService, 'broadcast')
    h.mock(radioStationStore, 'getSourceUrl', 'https://station.com/stream.mp3')

    await playbackService.play(toBePlayedStation)

    expect(playMock).toHaveBeenCalled()
    expect(playbackService.player.media.src).toBe('https://station.com/stream.mp3')
    expect(currentStation.playback_state).toBe('Stopped')
    expect(toBePlayedStation.playback_state).toBe('Playing')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', toBePlayedStation)
  })

  it('pauses a radio station playback', async () => {
    const currentStation = h.factory('radio-station')
    currentStation.playback_state = 'Playing'
    radioStationStore.state.stations = [currentStation]

    const pauseMock = h.mock(playbackService.player.media, 'pause')
    const broadcastMock = h.mock(socketService, 'broadcast')
    await playbackService.stop()

    expect(pauseMock).toHaveBeenCalled()
    expect(playbackService.player.media.src).toBe('')
    expect(currentStation.playback_state).toBe('Paused')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', currentStation)
  })
})
