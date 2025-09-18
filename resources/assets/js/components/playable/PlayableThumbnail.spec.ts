import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
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

  it('emits the event when clicked', async () => {
    h.createAudioPlayer()
    const { emitted } = renderComponent()
    await h.user.click(screen.getByRole('button'))

    expect(emitted().clicked).toBeTruthy()
  })
})
