import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { http } from '@/services'
import CreditsBlock from './CreditsBlock.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders the credits', async () => {
      window.IS_DEMO = true

      const getMock = this.mock(http, 'get').mockResolvedValue([
        { name: 'Foo', url: 'https://foo.com' },
        { name: 'Bar', url: 'https://bar.com' },
        { name: 'Something Else', url: 'https://something-else.net' }
      ])

      const { html } = this.render(CreditsBlock)

      await this.tick(3)
      expect(html()).toMatchSnapshot()
      expect(getMock).toHaveBeenCalledWith('demo/credits')

      window.IS_DEMO = false
    })
  }
}
