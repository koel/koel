import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './FavoriteButton.vue'

describe('favoriteButton.vue', () => {
  const h = createHarness()

  it.each([[true, 'Undo Favorite'], [false, 'Favorite']])(
    'renders and emits the toggle event when clicked',
    async (favorite, label) => {
      const { emitted } = h.render(Component, {
        props: {
          favorite,
        },
      })

      await h.user.click(screen.getByRole('button', { name: label }))
      expect(emitted()).toHaveProperty('toggle')
    },
  )
})
