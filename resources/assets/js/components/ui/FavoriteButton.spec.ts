import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './FavoriteButton.vue'

new class extends UnitTestCase {
  protected test () {
    it.each([[true, 'Undo Favorite'], [false, 'Favorite']])(
      'renders and emits the toggle event when clicked',
      async (favorite, label) => {
        const { emitted } = this.render(Component, {
          props: {
            favorite,
          },
        })

        await this.user.click(screen.getByRole('button', { name: label }))
        expect(emitted()).toHaveProperty('toggle')
      },
    )
  }
}
