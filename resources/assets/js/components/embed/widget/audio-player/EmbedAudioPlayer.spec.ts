import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EmbedAudioPlayer.vue'

describe('embedAudioPlayer.vue', () => {
  const h = createHarness()

  it('renders audio element', () => {
    const { container } = h.render(Component, {
      props: {
        playables: [],
        preview: false,
      },
    })

    expect(container.querySelector('audio')).toBeTruthy()
  })
})
