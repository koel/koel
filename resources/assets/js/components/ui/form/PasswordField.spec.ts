import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import PasswordField from './PasswordField.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders plain text', async () => {
      this.render(PasswordField)
      const input = screen.getByTestId('input')
      const toggle = screen.getByTestId('toggle')

      await this.trigger(toggle, 'click')
      expect(input.getAttribute('type')).toBe('text')

      await this.trigger(toggle, 'click')
      expect(input.getAttribute('type')).toBe('password')
    })
  }
}
