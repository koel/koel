import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { acl } from '@/services/acl'
import { screen, waitFor, within } from '@testing-library/vue'
import Component from './RolePicker.vue'

describe('rolePicker.vue', async () => {
  const h = createHarness()

  const renderComponent = async () => {
    const fetchRolesMock = h.mock(acl, 'fetchAssignableRoles').mockResolvedValueOnce([
      { id: 'admin', label: 'Admin', description: 'Full access to all system features' },
      { id: 'manager', label: 'Manager', description: 'Can edit content but has limited administrative privileges' },
      { id: 'user', label: 'User', description: 'Read-only access to content' },
    ])

    const rendered = h.render(Component)
    await waitFor(() => expect(fetchRolesMock).toHaveBeenCalled())

    return rendered
  }

  it('renders the roles and emits the proper event', async () => {
    const { emitted } = await renderComponent()
    await h.tick()

    const select = screen.getByRole('combobox')
    const options = within(select).getAllByRole('option')

    expect(options).toHaveLength(3)
    expect(options[0].textContent).toBe('Admin')
    expect(options[1].textContent).toBe('Manager')
    expect(options[2].textContent).toBe('User')

    await h.user.selectOptions(select, ['manager'])

    expect(emitted()['update:modelValue']).toEqual([['manager']])
  })
})
