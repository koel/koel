import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import Component from './EmbedAudioPlayerNextButton.vue'

describe('embedPlayerNextButton.vue', async () => {
  const h = createHarness()

  it('renders and works', async () => {
    const { emitted } = h.render(Component, {
      props: {
        playable: h.factory('song'),
      },
    })

    await h.user.click(screen.getByRole('button'))
    expect(emitted().clicked).toBeTruthy()
  })

  it('is disabled when there is no next song', async () => {
    h.render(Component)
    expect(screen.getByRole('button').hasAttribute('disabled')).toBe(true)
  })
})
