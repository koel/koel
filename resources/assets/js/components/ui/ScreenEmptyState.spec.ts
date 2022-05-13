import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import ScreenEmptyState from './ScreenEmptyState.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(ScreenEmptyState, {
        slots: {
          icon: '<i class="my-icon"/>',
          default: 'Nothing here'
        }
      }).html()).toMatchSnapshot()
    })
  }
}
