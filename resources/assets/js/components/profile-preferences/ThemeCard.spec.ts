import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import ThemeCard from './ThemeCard.vue'

const theme: Theme = {
  id: 'sample',
  thumbnailColor: '#f00'
}

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('emits an event when selected', async () => {
      const { emitted } = this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Sample' }))

      expect(emitted().selected[0]).toEqual([theme])
    })
  }

  private renderComponent () {
    return this.render(ThemeCard, {
      props: {
        theme
      }
    })
  }
}
