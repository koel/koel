import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AlbumOrArtistCardMoreActionsButton.vue'

describe('albumOrArtistCardMoreActionsButton.vue', () => {
  const h = createHarness()

  it('emits click when clicked', async () => {
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'More actions' }))

    expect(emitted('click')).toHaveLength(1)
  })
})
