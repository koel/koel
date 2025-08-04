import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './SponsorList.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(Component).html()).toMatchSnapshot()
    })
  }
}
