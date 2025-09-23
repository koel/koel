import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { authService } from '@/services/authService'
import Component from './ProfileForm.vue'

describe('profileForm.vue', () => {
  const h = createHarness()

  const renderComponent = (user: User) => {
    return h.actingAsUser(user).render(Component)
  }

  it('updates profile', async () => {
    const updateMock = h.mock(authService, 'updateProfile')
    const alertMock = h.mock(MessageToasterStub.value, 'success')

    renderComponent(h.factory('user', {
      avatar: 'https://gravatar.com/foo',
    }))

    await h.type(screen.getByTestId('currentPassword'), 'old-password')
    await h.type(screen.getByTestId('email'), 'koel@example.com')
    await h.type(screen.getByTestId('name'), 'Koel User')
    await h.type(screen.getByTestId('newPassword'), 'new-password')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith({
      name: 'Koel User',
      email: 'koel@example.com',
      current_password: 'old-password',
      new_password: 'new-password',
      avatar: 'https://gravatar.com/foo',
    })

    expect(alertMock).toHaveBeenCalledWith('Profile updated.')
  })
})
