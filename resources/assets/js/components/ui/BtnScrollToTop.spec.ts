import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { $ } from '@/utils/$'
import Component from './BtnScrollToTop.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(Component).html()).toMatchSnapshot()
    })

    it('scrolls to top', async () => {
      const mock = this.mock($, 'scrollTo')
      this.render(Component)

      await this.user.click(screen.getByTitle('Scroll to top'))

      expect(mock).toHaveBeenCalled()
    })
  }
}
