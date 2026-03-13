import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EmbedAudioPlayerProgressBar.vue'

describe('embedAudioPlayerProgressBar.vue', () => {
  const h = createHarness()

  it('renders with progress', () => {
    const playable = h.factory('song', { playback_state: 'Playing' })

    const { container } = h.render(Component, {
      props: { playable, progress: 50 },
    })

    expect(container.querySelector('.progress-bar')).not.toBeNull()
  })

  it('does not emit seek when no playable is playing', async () => {
    const { container, emitted } = h.render(Component, {
      props: { progress: 0 },
    })

    await h.user.click(container.querySelector('.progress-bar')!)
    expect(emitted().seek).toBeUndefined()
  })
})
