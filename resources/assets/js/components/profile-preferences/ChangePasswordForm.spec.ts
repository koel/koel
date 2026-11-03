import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
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

  it('sets a first password without asking for the current one', async () => {
    const changeMock = h.mock(authService, 'changePassword')
    const user = h.factory('user').make({ sso_provider: null, has_password: false }) as CurrentUser

    h.actingAsUser(user).render(Component)

    expect(screen.queryByTestId('current-password')).toBeNull()

    await h.type(screen.getByTestId('new-password'), 'new-secret-1234')
    await h.user.click(screen.getByRole('button', { name: 'Set Password' }))

    expect(changeMock).toHaveBeenCalledWith(undefined, 'new-secret-1234')
    await waitFor(() => screen.getByTestId('current-password'))
  })

  it('lets a single sign-on user set a password', async () => {
    const changeMock = h.mock(authService, 'changePassword')
    const user = h.factory('user').make({ sso_provider: 'Google', has_password: false }) as CurrentUser

    h.actingAsUser(user).render(Component)

    await h.type(screen.getByTestId('new-password'), 'new-secret-1234')
    await h.user.click(screen.getByRole('button', { name: 'Set Password' }))

    expect(changeMock).toHaveBeenCalledWith(undefined, 'new-secret-1234')
  })

  it('hides the form for reverse proxy users', () => {
    h.actingAsUser(h.factory('user').make({ sso_provider: 'Reverse Proxy' }) as CurrentUser).render(Component)

    expect(screen.queryByTestId('new-password')).toBeNull()
    expect(screen.queryByRole('button', { name: 'Update Password' })).toBeNull()
  })
})
