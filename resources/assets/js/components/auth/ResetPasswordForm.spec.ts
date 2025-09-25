import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import Component from './ResetPasswordForm.vue'

describe('resetPasswordForm.vue', () => {
  const h = createHarness()

  it('resets password', async () => {
    const resetMock = h.mock(authService, 'resetPassword').mockResolvedValue(null)
    const loginMock = h.mock(authService, 'login').mockResolvedValue(null)

    h.visit('/reset-password/Zm9vQGJhci5jb218bXktdG9rZW4=').render(Component)

    await h.type(screen.getByPlaceholderText('New password'), 'new-password')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(resetMock).toHaveBeenCalledWith('foo@bar.com', 'new-password', 'my-token')
    expect(loginMock).toHaveBeenCalledWith('foo@bar.com', 'new-password')
  })
})
