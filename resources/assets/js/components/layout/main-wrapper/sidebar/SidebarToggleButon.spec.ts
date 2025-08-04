import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './SidebarToggleButton.vue'

new class extends UnitTestCase {
  protected test () {
    it('emits the toggle event', () => {
      const { emitted } = this.render(Component)
      this.trigger(screen.getByRole('checkbox'), 'click')
      expect(emitted()['update:modelValue']).toBeTruthy()
    })
  }
}
