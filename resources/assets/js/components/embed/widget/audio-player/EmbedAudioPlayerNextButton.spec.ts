import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import Component from './EmbedAudioPlayerNextButton.vue'

describe('embedPlayerNextButton.vue', async () => {
  const h = createHarness()

  it('is disabled when there is no next song', async () => {
    h.render(Component)
    expect(screen.getByRole('button').hasAttribute('disabled')).toBe(true)
  })
})
