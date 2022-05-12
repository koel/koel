import { expect, it } from 'vitest'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import ScreenHeader from './ScreenHeader.vue'

new class extends ComponentTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(ScreenHeader, {
        slots: {
          default: 'This Header',
          meta: '<p>Some meta</p>',
          controls: '<nav>Some controls</nav>'
        }
      }).html()).toMatchSnapshot()
    })
  }
}
