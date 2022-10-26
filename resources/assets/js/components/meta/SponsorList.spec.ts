import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SponsorList from './SponsorList.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(SponsorList).html()).toMatchSnapshot()
    })
  }
}
