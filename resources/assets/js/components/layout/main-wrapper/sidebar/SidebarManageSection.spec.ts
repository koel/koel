import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './SidebarManageSection.vue'

new class extends UnitTestCase {
  protected test () {
    it('shows all menu items if current user is an admin', () => {
      this.beAdmin().render(Component)
      screen.getByText('Settings')
      screen.getByText('Users')
      screen.getByText('Upload')
    })

    it('shows nothing if current user is not an admin', () => {
      this.be().render(Component)
      expect(screen.queryByText('Settings')).toBeNull()
      expect(screen.queryByText('Upload')).toBeNull()
      expect(screen.queryByText('Users')).toBeNull()
    })

    it('shows only the upload menu item if current user is a Plus user', () => {
      this.be().enablePlusEdition().render(Component)
      screen.getByText('Upload')
      expect(screen.queryByText('Settings')).toBeNull()
      expect(screen.queryByText('Users')).toBeNull()
    })
  }
}
