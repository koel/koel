import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { screen } from '@testing-library/vue'
import { faHome } from '@fortawesome/free-solid-svg-icons'
import SidebarItem from './SidebarItem.vue'

new class extends UnitTestCase {
  private renderComponent () {
    return this.render(SidebarItem, {
      props: {
        icon: faHome,
        href: '#',
        screen: 'Home'
      },
      slots: {
        default: 'Home'
      }
    })
  }

  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('activates when the screen matches', async () => {
      this.renderComponent()

      await this.router.activateRoute({
        screen: 'Home',
        path: '_'
      })

      expect(screen.getByRole('link').classList.contains('active')).toBe(true)
    })
  }
}
