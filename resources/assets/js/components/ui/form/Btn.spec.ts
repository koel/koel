import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './Btn.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(Component, {
        slots: {
          default: 'Click Me Nao',
        },
      }).html()).toMatchSnapshot()
    })
  }
}
