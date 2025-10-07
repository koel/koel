import { fireEvent, screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { userStore } from '@/stores/userStore'
import Component from './EditUserForm.vue'

describe('editUserForm.vue', () => {
  const h = createHarness()

  const renderComponent = (user?: User) => {
    user = user ?? h.factory('user')

    const rendered = h.render(Component, {
      props: {
        user,
      },
      global: {
        stubs: {
          RolePicker: h.stub('role-picker', true),
        },
      },
    })

    return {
      ...rendered,
      user,
    }
  }

  it('edits a user', async () => {
    const updateMock = h.mock(userStore, 'update')
    const alertMock = h.mock(MessageToasterStub.value, 'success')

    const { user } = renderComponent()

    await h.type(screen.getByRole('textbox', { name: 'name' }), 'Jane Doe')
    await h.type(screen.getByPlaceholderText('Leave blank for no changes'), 'new-password-duck')
    await fireEvent.update(screen.getByTestId('role-picker'), 'manager')
    await h.user.click(screen.getByRole('button', { name: 'Update' }))

    await waitFor(() => {
      expect(updateMock).toHaveBeenCalledWith(user, {
        name: 'Jane Doe',
        email: user.email,
        role: 'manager',
        password: 'new-password-duck',
      })

      expect(alertMock).toHaveBeenCalledWith('User profile updated.')
    })
  })
})
