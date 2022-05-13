import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import { $ } from '@/utils'
import UnitTestCase from '@/__tests__/UnitTestCase'
import BtnScrollToTop from './BtnScrollToTop.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(BtnScrollToTop).html()).toMatchSnapshot()
    })

    it('scrolls to top', async () => {
      const mock = this.mock($, 'scrollTo')
      const { getByTitle } = this.render(BtnScrollToTop)

      await fireEvent.click(getByTitle('Scroll to top'))

      expect(mock).toHaveBeenCalled()
    })
  }
}
