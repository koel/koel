import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import Component from './EmbedAudioPlayerPlayButton.vue'

describe('embedAudioPlayerPlayButton.vue', async () => {
  const h = createHarness()

  const renderComponent = (playable?: Playable, preview = false) => {
    const rendered = h.render(Component, {
      props: {
        playable,
        preview,
      },
    })

    return {
      ...rendered,
      playable,
    }
  }

  it.each<[PlaybackState | undefined, string, string]>([
    [undefined, 'icon-play', 'Play/Resume'],
    ['Playing', 'icon-pause', 'Pause'],
    ['Paused', 'icon-play', 'Play/Resume'],
    ['Stopped', 'icon-play', 'Play/Resume'],
  ])('renders and functions state %s with proper text and icon', async (playbackState, iconTestId, buttonText) => {
    const playable = playbackState === undefined
      ? undefined
      : h.factory('song', {
          playback_state: playbackState,
        })

    const { emitted } = renderComponent(playable)

    expect(screen.getByTestId('wrapper').classList.contains('preview-wrapper')).toBe(false)
    screen.getByTestId(iconTestId)
    await h.user.click(screen.getByRole('button', { name: buttonText }))
    expect(emitted().clicked).toBeTruthy()
  })

  it('applies a .preview-wrapper class in Preview mode', () => {
    renderComponent(h.factory('song'), true)
    expect(screen.getByTestId('wrapper').classList.contains('preview-wrapper')).toBe(true)
  })
})
