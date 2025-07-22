import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './YouTubeSidebarItem.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      const { html } = this.render(Component, {
        slots: {
          default: 'Another One Bites the Dust',
        },
      })

      expect(html()).toMatchSnapshot()
    })
  }
}
