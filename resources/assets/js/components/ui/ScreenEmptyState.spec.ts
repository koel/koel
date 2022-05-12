import { expect, it } from 'vitest'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import ScreenEmptyState from './ScreenEmptyState.vue'

new class extends ComponentTestCase {
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
