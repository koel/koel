import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
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
      this.render(BtnScrollToTop)

      await this.user.click(screen.getByTitle('Scroll to top'))

      expect(mock).toHaveBeenCalled()
    })
  }
}
