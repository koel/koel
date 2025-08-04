import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './ScreenEmptyState.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(Component, {
        slots: {
          icon: '<i class="my-icon"/>',
          default: 'Nothing here',
        },
      }).html()).toMatchSnapshot()
    })
  }
}
