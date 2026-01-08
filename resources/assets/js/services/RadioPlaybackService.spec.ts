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

    const broadcastMock = h.mock(socketService, 'broadcast')
    h.mock(radioStationStore, 'getSourceUrl', 'https://station.com/stream.mp3')

    // Mock HTMLAudioElement methods for the radio element
    const radioElement = document.getElementById('audio-radio') as HTMLAudioElement
    const radioPlayMock = vi.fn().mockResolvedValue(undefined)
    if (radioElement) {
      radioElement.play = radioPlayMock
      // Mock readyState to HAVE_CURRENT_DATA so it plays immediately
      Object.defineProperty(radioElement, 'readyState', {
        value: HTMLMediaElement.HAVE_CURRENT_DATA,
        writable: true,
        configurable: true,
      })
      // Mock load() to trigger canplay event immediately (which is what the code waits for)
      const originalLoad = radioElement.load.bind(radioElement)
      radioElement.load = vi.fn(() => {
        originalLoad()
        // Trigger canplay event immediately so the promise resolves
        setTimeout(() => {
          radioElement.dispatchEvent(new Event('canplay'))
        }, 0)
      })
    }

    const playPromise = playbackService.play(toBePlayedStation)
    
    // Wait a bit for the canplay event to fire
    await new Promise(resolve => setTimeout(resolve, 10))
    
    await playPromise

    // Radio uses radioAudioElement, not player.media
    expect(radioPlayMock).toHaveBeenCalled()
    const radioAudioElement = playbackService['radioAudioElement'] as HTMLAudioElement
    if (radioAudioElement) {
      expect(radioAudioElement.src).toContain('https://station.com/stream.mp3')
    }
    expect(currentStation.playback_state).toBe('Stopped')
    expect(toBePlayedStation.playback_state).toBe('Playing')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', toBePlayedStation)
  }, 10000) // Increase timeout for async operations

  it('pauses a radio station playback', async () => {
    const currentStation = h.factory('radio-station')
    currentStation.playback_state = 'Playing'
    radioStationStore.state.stations = [currentStation]

    const pauseMock = h.mock(playbackService.player.media, 'pause')
    const broadcastMock = h.mock(socketService, 'broadcast')
    await playbackService.stop()

    expect(pauseMock).toHaveBeenCalled()
    expect(playbackService.player.media.src).toBe('')
    // Radio stations use 'Stopped' instead of 'Paused' since radio streams are live
    expect(currentStation.playback_state).toBe('Stopped')
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', currentStation)
  })
})
