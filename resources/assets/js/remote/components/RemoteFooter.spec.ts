import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { socketService } from '@/services/socketService'
import Component from './RemoteFooter.vue'

describe('remoteFooter.vue', () => {
  const h = createHarness()

  const renderComponent = (streamable?: Streamable) => {
    streamable = streamable || h.factory('song')

    h.render(Component, {
      props: {
        streamable,
      },
      global: {
        components: {
          Icon: h.stub('Icon'),
          VolumeControl: h.stub('volume-control'),
        },
        provide: {
          state: {
            streamable,
            volume: 7,
          },
        },
      },
    })
  }

  it('toggles like', async () => {
    const broadcastMock = h.mock(socketService, 'broadcast')
    const playable = h.factory('song', { favorite: false })
    renderComponent(playable)

    await h.user.click(screen.getByTestId('btn-toggle-favorite'))
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_TOGGLE_FAVORITE')
    expect(playable.favorite).toBe(true)
  })

  it('plays previous', async () => {
    const broadcastMock = h.mock(socketService, 'broadcast')
    renderComponent()

    await h.user.click(screen.getByTestId('btn-play-prev'))
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_PLAY_PREV')
  })

  it('plays next', async () => {
    const broadcastMock = h.mock(socketService, 'broadcast')
    renderComponent()

    await h.user.click(screen.getByTestId('btn-play-next'))
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_PLAY_NEXT')
  })

  it.each<[string, PlaybackState, PlaybackState]>([
    ['pauses', 'Playing', 'Paused'],
    ['resumes', 'Paused', 'Playing'],
    ['starts', 'Stopped', 'Playing'],
  ])('%s playback', async (_, currentState, newState) => {
    const broadcastMock = h.mock(socketService, 'broadcast')
    const playable = h.factory('episode', { playback_state: currentState })
    renderComponent(playable)

    await h.user.click(screen.getByTestId('btn-toggle-playback'))
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_TOGGLE_PLAYBACK')
    expect(playable.playback_state).toBe(newState)
  })
})
