import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { faHome } from '@fortawesome/free-solid-svg-icons'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils/eventBus'
import Component from './SidebarItem.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('activates when the screen matches', async () => {
      this.renderComponent()

      await this.router.activateRoute({
        screen: 'Home',
        path: '_',
      })

      expect(screen.getByTestId('sidebar-item').classList.contains('current')).toBe(true)
    })

    it('emits the sidebar toggle event when clicked', async () => {
      const mock = this.mock(eventBus, 'emit')
      this.renderComponent()
      await this.user.click(screen.getByTestId('sidebar-item'))

      expect(mock).toHaveBeenCalledWith('TOGGLE_SIDEBAR')
    })
  }

  private renderComponent () {
    return this.render(Component, {
      props: {
        icon: faHome,
        href: '#',
        screen: 'Home',
      },
      slots: {
        default: 'Home',
      },
    })
  }
}
