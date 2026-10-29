import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import Component from './ChangePasswordForm.vue'

describe('changePasswordForm.vue', () => {
  const h = createHarness()

  it('changes the password', async () => {
    const changeMock = h.mock(authService, 'changePassword')

    h.actingAsUser(h.factory('user').make({ sso_provider: null }) as CurrentUser).render(Component)

    await h.type(screen.getByTestId('current-password'), 'old-secret')
    await h.type(screen.getByTestId('new-password'), 'new-secret-1234')
    await h.user.click(screen.getByRole('button', { name: 'Update Password' }))

    expect(changeMock).toHaveBeenCalledWith('old-secret', 'new-secret-1234')
  })

  it('hides the form for SSO users', () => {
    h.actingAsUser(h.factory('user').make({ sso_provider: 'Google' }) as CurrentUser).render(Component)

    expect(screen.queryByTestId('change-password-form')).toBeNull()
  })
})
