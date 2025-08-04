import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './ThemeCard.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('emits an event when selected', async () => {
      const { emitted, theme } = this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Sample' }))

      expect(emitted().selected[0]).toEqual([theme])
    })
  }

  private renderComponent () {
    const theme: Theme = {
      id: 'sample',
      thumbnailColor: '#f00',
    }

    const rendered = this.render(Component, {
      props: {
        theme,
      },
    })

    return {
      ...rendered,
      theme,
    }
  }
}
