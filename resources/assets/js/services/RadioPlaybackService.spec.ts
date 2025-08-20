import plyr from 'plyr'
import { expect, it, vi } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { radioStationStore } from '@/stores/radioStationStore'
import { socketService } from '@/services/socketService'
import { playbackService } from '@/services/RadioPlaybackService'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      this.createAudioPlayer()
      playbackService.activate(document.querySelector('.plyr')!)
    })
  }

  protected test () {
    it('only initializes once', () => {
      const spy = vi.spyOn(plyr, 'setup')

      playbackService.activate(document.querySelector('.plyr')!)
      expect(spy).not.toHaveBeenCalled()
    })

    it('plays a radio station', async () => {
      const currentStation = factory('radio-station')
      currentStation.playback_state = 'Playing'
      const toBePlayedStation = factory('radio-station')
      toBePlayedStation.playback_state = 'Stopped'

      radioStationStore.state.stations = [currentStation, toBePlayedStation]

      const playMock = this.mock(playbackService.player.media, 'play')
      const broadcastMock = this.mock(socketService, 'broadcast')
      this.mock(radioStationStore, 'getSourceUrl', 'https://station.com/stream.mp3')

      await playbackService.play(toBePlayedStation)

      expect(playMock).toHaveBeenCalled()
      expect(playbackService.player.media.src).toBe('https://station.com/stream.mp3')
      expect(currentStation.playback_state).toBe('Stopped')
      expect(toBePlayedStation.playback_state).toBe('Playing')
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', toBePlayedStation)
    })

    it('pauses a radio station playback', async () => {
      const currentStation = factory('radio-station')
      currentStation.playback_state = 'Playing'
      radioStationStore.state.stations = [currentStation]

      const pauseMock = this.mock(playbackService.player.media, 'pause')
      const broadcastMock = this.mock(socketService, 'broadcast')
      await playbackService.stop()

      expect(pauseMock).toHaveBeenCalled()
      expect(playbackService.player.media.src).toBe('')
      expect(currentStation.playback_state).toBe('Paused')
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_STREAMABLE', currentStation)
    })
  }
}
