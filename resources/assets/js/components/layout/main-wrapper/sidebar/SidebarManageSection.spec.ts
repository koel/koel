import { describe, expect, it } from 'vite-plus/test'
import { faTools } from '@fortawesome/free-solid-svg-icons'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { Filter } from '@/config/hooks'
import { addFilter, removeFilter } from '@/hooks'
import Component, { type ManageSidebarItem } from './SidebarManageSection.vue'

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

  it.each([
    ['Trial', 1],
    [null, 0],
  ])('shows a badge only for an item that has one (%s)', (badge, badgeCount) => {
    const handle = addFilter<ManageSidebarItem[]>(Filter.MANAGE_SIDEBAR_ITEMS, items => [
      ...items,
      { label: 'Extra', icon: faTools, route: 'home', screens: ['Home'], visible: () => true, badge: () => badge },
    ])

    h.actingAsUser().render(Component)

    expect(screen.queryAllByTestId('sidebar-item-badge')).toHaveLength(badgeCount)

    removeFilter(handle)
  })
})
