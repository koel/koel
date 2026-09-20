import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { authService } from '@/services/authService'
import Component from './ForgotPasswordForm.vue'

describe('forgotPasswordForm.vue', () => {
  const h = createHarness()

  it('requests reset password link', async () => {
    const requestMock = h.mock(authService, 'requestResetPasswordLink').mockResolvedValue(null)
    h.render(Component)
    await h.type(screen.getByPlaceholderText('Your email address'), 'foo@bar.com')
    await h.user.click(screen.getByText('Reset Password'))

    expect(requestMock).toHaveBeenCalledWith('foo@bar.com')
  })

  it('does not say whether the address has an account', async () => {
    h.mock(authService, 'requestResetPasswordLink').mockResolvedValue(null)
    const successMock = h.mock(MessageToasterStub.value, 'success')
    h.render(Component)
    await h.type(screen.getByPlaceholderText('Your email address'), 'foo@bar.com')
    await h.user.click(screen.getByText('Reset Password'))
    await h.tick()

    expect(successMock).toHaveBeenCalledWith('If that address has an account, a password reset link is on its way.')
  })

  it('cancels', async () => {
    const { emitted } = h.render(Component)
    await h.user.click(screen.getByText('Cancel'))

    expect(emitted().cancel).toBeTruthy()
  })
})
