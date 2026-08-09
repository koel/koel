import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { authService } from '@/services/authService'
import { userStore } from '@/stores/userStore'
import Component from './ProfileForm.vue'

describe('profileForm.vue', () => {
  const h = createHarness()

  const renderComponent = (user: CurrentUser) => {
    return h.actingAsUser(user).render(Component)
  }

  it('updates profile', async () => {
    const updateMock = h.mock(authService, 'updateProfile')
    const alertMock = h.mock(MessageToasterStub.value, 'success')

    renderComponent(
      h.factory('user').make({
        avatar: 'https://gravatar.com/foo',
      }) as CurrentUser,
    )

    await h.type(screen.getByTestId('email'), 'koel@example.com')
    await h.type(screen.getByTestId('name'), 'Koel User')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith({
      name: 'Koel User',
      email: 'koel@example.com',
      avatar: undefined,
    })

    expect(alertMock).toHaveBeenCalledWith('Profile updated.')
  })

  it('refreshes the avatar after updating the email address', async () => {
    h.mock(authService, 'updateProfile', async () => {
      userStore.state.current.avatar = 'https://gravatar.com/new-email'
    })

    renderComponent(
      h.factory('user').make({
        avatar: 'https://gravatar.com/old-email',
      }) as CurrentUser,
    )

    await h.type(screen.getByTestId('email'), 'new@example.com')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => expect(screen.getByRole('img').getAttribute('src')).toBe('https://gravatar.com/new-email'))
  })
})
