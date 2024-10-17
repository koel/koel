import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Magnifier from './Magnifier.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders and functions', async () => {
      const { html, emitted } = this.render(Magnifier)

      await this.user.click(screen.getByRole('button', { name: 'Zoom in' }))
      expect(emitted().in).toBeTruthy()

      await this.user.click(screen.getByRole('button', { name: 'Zoom out' }))
      expect(emitted().out).toBeTruthy()

      expect(html()).toMatchSnapshot()
    })
  }
}
