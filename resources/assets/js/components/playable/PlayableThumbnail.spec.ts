import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/QueuePlaybackService'
import { queueStore } from '@/stores/queueStore'
import Component from './PlayableThumbnail.vue'

describe('playableThumbnail.vue', () => {
  const h = createHarness()

  const renderComponent = (playbackState: PlaybackState = 'Stopped') => {
    const playable = h.factory('song', {
      playback_state: playbackState,
      play_count: 10,
      title: 'Foo bar',
    })

    const rendered = h.render(Component, {
      props: {
        playable,
      },
    })

    return {
      ...rendered,
      playable,
    }
  }

  it.each<[PlaybackState, MethodOf<typeof playbackService>]>([
    ['Stopped', 'play'],
    ['Playing', 'pause'],
    ['Paused', 'resume'],
  ])('if state is currently "%s", %ss', async (state, method) => {
    h.createAudioPlayer()

    h.mock(queueStore, 'queueIfNotQueued')
    const playbackMock = h.mock(playbackService, method)
    renderComponent(state)

    await h.user.click(screen.getByRole('button'))

    expect(playbackMock).toHaveBeenCalled()
  })
})
