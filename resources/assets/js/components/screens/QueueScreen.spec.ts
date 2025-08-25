import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { queueStore } from '@/stores/queueStore'
import { playbackService } from '@/services/QueuePlaybackService'
import Component from './QueueScreen.vue'

describe('queueScreen.vue', () => {
  const h = createHarness()

  const renderComponent = (playables: Playable[] = []) => {
    queueStore.state.playables = playables

    h.render(Component, {
      global: {
        stubs: {
          PlayableList: h.stub('song-list'),
        },
      },
    })
  }

  it('renders the queue', () => {
    renderComponent(h.factory('song', 3))

    expect(screen.queryByTestId('song-list')).toBeTruthy()
    expect(screen.queryByTestId('screen-empty-state')).toBeNull()
  })

  it('renders an empty state if no songs queued', () => {
    renderComponent()

    expect(screen.queryByTestId('song-list')).toBeNull()
    expect(screen.queryByTestId('screen-empty-state')).toBeTruthy()
  })

  it('has an option to plays some random songs if the library is not empty', async () => {
    h.createAudioPlayer()

    commonStore.state.song_count = 300
    const fetchRandomMock = h.mock(queueStore, 'fetchRandom')
    const playMock = h.mock(playbackService, 'playFirstInQueue')

    renderComponent()
    await h.user.click(screen.getByText('playing some random songs'))

    await waitFor(() => {
      expect(fetchRandomMock).toHaveBeenCalled()
      expect(playMock).toHaveBeenCalled()
    })
  })

  it('shuffles all', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 3)
    renderComponent(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    await h.user.click(screen.getByTitle('Shuffle all. Press Alt/⌥ to change mode.'))
    await waitFor(() => expect(playMock).toHaveBeenCalledWith(songs, true))
  })
})
