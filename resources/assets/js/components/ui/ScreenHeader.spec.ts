import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import ScreenHeader from './ScreenHeader.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(ScreenHeader, {
        slots: {
          default: 'This Header',
          meta: '<p>Some meta</p>',
          controls: '<nav>Some controls</nav>',
          thumbnail: '<img src="https://placekitten.com/200/300" />'
        }
      }).html()).toMatchSnapshot()
    })
  }
}
