import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { socketService } from '@/services'
import Component from './RemoteFooter.vue'

new class extends UnitTestCase {
  private renderComponent (playable?: Playable) {
    playable = playable || factory('song')

    this.render(Component, {
      props: {
        playable
      },
      global: {
        components: {
          Icon: this.stub('Icon'),
          VolumeControl: this.stub('volume-control')
        },
        provide: {
          state: {
            playable,
            volume: 7
          }
        }
      }
    })
  }

  protected test () {
    it('toggles like', async () => {
      const broadcastMock = this.mock(socketService, 'broadcast')
      const playable = factory('song', { liked: false })
      this.renderComponent(playable)

      await this.user.click(screen.getByTestId('btn-toggle-favorite'))
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_TOGGLE_FAVORITE')
      expect(playable.liked).toBe(true)
    })

    it('plays previous', async () => {
      const broadcastMock = this.mock(socketService, 'broadcast')
      this.renderComponent()

      await this.user.click(screen.getByTestId('btn-play-prev'))
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_PLAY_PREV')
    })

    it('plays next', async () => {
      const broadcastMock = this.mock(socketService, 'broadcast')
      this.renderComponent()

      await this.user.click(screen.getByTestId('btn-play-next'))
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_PLAY_NEXT')
    })

    it.each<[string, PlaybackState, PlaybackState]>([
      ['pauses', 'Playing', 'Paused'],
      ['resumes', 'Paused', 'Playing'],
      ['starts', 'Stopped', 'Playing']
    ])('%s playback', async (_, currentState, newState) => {
      const broadcastMock = this.mock(socketService, 'broadcast')
      const playable = factory('episode', { playback_state: currentState })
      this.renderComponent(playable)

      await this.user.click(screen.getByTestId('btn-toggle-playback'))
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_TOGGLE_PLAYBACK')
      expect(playable.playback_state).toBe(newState)
    })
  }
}
