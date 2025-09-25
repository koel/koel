import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SidebarManageSection.vue'

describe('sidebarManageSection.vue', () => {
  const h = createHarness()

  it('shows all menu items if current user is an admin', () => {
    h.actingAsAdmin().render(Component)
    screen.getByText('Settings')
    screen.getByText('Users')
    screen.getByText('Upload')
  })

  it('shows nothing if current user is not an admin', () => {
    h.actingAsUser().render(Component)
    expect(screen.queryByText('Settings')).toBeNull()
    expect(screen.queryByText('Upload')).toBeNull()
    expect(screen.queryByText('Users')).toBeNull()
  })

  it('shows only the upload menu item if current user is a Plus user', () => {
    h.actingAsUser().withPlusEdition(() => {
      h.render(Component)
      screen.getByText('Upload')
      expect(screen.queryByText('Settings')).toBeNull()
      expect(screen.queryByText('Users')).toBeNull()
    })
  })
})
