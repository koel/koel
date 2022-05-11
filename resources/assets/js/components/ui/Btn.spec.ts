import { expect, it } from 'vitest'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import Btn from './Btn.vue'

new class extends ComponentTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(Btn, {
        slots: {
          default: 'Click Me Nao'
        }
      }).html()).toMatchSnapshot()
    })
  }
}
