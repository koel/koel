import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
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

  it('cancels', async () => {
    const { emitted } = h.render(Component)
    await h.user.click(screen.getByText('Cancel'))

    expect(emitted().cancel).toBeTruthy()
  })
})
