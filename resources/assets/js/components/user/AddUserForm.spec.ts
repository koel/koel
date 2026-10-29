import { describe, expect, it } from 'vite-plus/test'
import { fireEvent, screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { userStore } from '@/stores/userStore'
import Component from './AddUserForm.vue'

describe('addUserForm.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component, {
      global: {
        stubs: {
          RolePicker: h.stub('role-picker', true),
        },
      },
    })
  }

  it('creates a new user', async () => {
    const storeMock = h.mock(userStore, 'store').mockResolvedValue(
      h.factory('user').make({
        name: 'John Doe',
        email: 'john@doe.com',
      }),
    )

    renderComponent()

    await h.type(screen.getByRole('textbox', { name: 'name' }), 'John Doe')
    await h.type(screen.getByRole('textbox', { name: 'email' }), 'john@doe.com')
    await h.type(screen.getByLabelText('Password'), 'secret-password')
    await fireEvent.update(screen.getByTestId('role-picker'), 'admin')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      expect(storeMock).toHaveBeenCalledWith({
        name: 'John Doe',
        email: 'john@doe.com',
        password: 'secret-password',
        role: 'admin',
      })
    })
  })
})
