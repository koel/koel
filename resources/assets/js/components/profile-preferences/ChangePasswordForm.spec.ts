import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { authService } from '@/services/authService'
import Component from './ChangePasswordForm.vue'

describe('changePasswordForm.vue', () => {
  const h = createHarness()

  it('changes the password', async () => {
    const changeMock = h.mock(authService, 'changePassword')
    const successMock = h.mock(MessageToasterStub.value, 'success')

    h.actingAsUser(h.factory('user').make({ sso_provider: null }) as CurrentUser).render(Component)

    await h.type(screen.getByTestId('current-password'), 'old-secret')
    await h.type(screen.getByTestId('new-password'), 'new-secret-1234')
    await h.user.click(screen.getByRole('button', { name: 'Update Password' }))

    expect(changeMock).toHaveBeenCalledWith('old-secret', 'new-secret-1234')
    expect(successMock).toHaveBeenCalledWith('Password updated.')
  })

  it('hides the form for SSO users', () => {
    h.actingAsUser(h.factory('user').make({ sso_provider: 'Google' }) as CurrentUser).render(Component)

    expect(screen.queryByTestId('change-password-form')).toBeNull()
  })
})
