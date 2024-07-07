import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import YouTubeSidebarItem from './YouTubeSidebarItem.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      const { html } = this.render(YouTubeSidebarItem, {
        slots: {
          default: 'Another One Bites the Dust',
        },
      })

      expect(html()).toMatchSnapshot()
    })
  }
}
