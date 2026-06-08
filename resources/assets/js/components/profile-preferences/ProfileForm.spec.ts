import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { authService } from '@/services/authService'
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
      avatar: 'https://gravatar.com/foo',
    })

    expect(alertMock).toHaveBeenCalledWith('Profile updated.')
  })

  it('disables save in demo mode', async () => {
    await h.withDemoMode(async () => {
      h.actingAsUser(h.factory('user').make() as CurrentUser).render(Component)

      expect(screen.getByRole<HTMLButtonElement>('button', { name: 'Save' }).disabled).toBe(true)
      screen.getByText('Profile updates are disabled in the demo version.')
    })
  })
})
