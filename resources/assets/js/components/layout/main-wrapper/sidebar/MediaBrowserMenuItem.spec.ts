import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import MediaBrowserMenuItem from './MediaBrowserMenuItem.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(MediaBrowserMenuItem).html()).toMatchSnapshot()
    })

    it('keeps track of the active path', async () => {
      const { html } = this.render(MediaBrowserMenuItem)

      await this.router.activateRoute({
        path: '_',
        screen: 'MediaBrowser',
      }, {
        path: 'foo/bar',
      })

      await this.tick(2)

      expect(html()).toMatchSnapshot()
    })
  }
}
