import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { screen, within } from '@testing-library/vue'
import Component from './RolePicker.vue'

describe('rolePicker.vue', async () => {
  const h = createHarness()

  const renderComponent = () => {
    commonStore.state.assignable_roles = [
      { id: 'admin', label: 'Admin', description: 'Full access to all system features' },
      { id: 'manager', label: 'Manager', description: 'Can edit content but has limited administrative privileges' },
      { id: 'user', label: 'User', description: 'Read-only access to content' },
    ]

    return h.render(Component)
  }

  it('renders the roles and emits the proper event', async () => {
    const { emitted } = renderComponent()
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
