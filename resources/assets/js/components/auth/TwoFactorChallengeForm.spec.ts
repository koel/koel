import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import Component from './TwoFactorChallengeForm.vue'

describe('twoFactorChallengeForm.vue', () => {
  const h = createHarness({ authenticated: false })

  it('submits the code and emits verified on success', async () => {
    const challengeMock = h.mock(authService, 'submitTwoFactorChallenge')
    const { emitted } = h.render(Component, { props: { loginToken: 'login-token-abc' } })

    await h.type(screen.getByPlaceholderText('Authentication code'), '123456')
    await h.user.click(screen.getByTestId('submit'))

    expect(challengeMock).toHaveBeenCalledWith('login-token-abc', '123456')
    expect(emitted().verified).toBeTruthy()
  })

  it('emits cancel when the back link is clicked', async () => {
    const { emitted } = h.render(Component, { props: { loginToken: 'login-token-abc' } })

    await h.user.click(screen.getByText('Back to login'))

    expect(emitted().cancel).toBeTruthy()
  })

  it('marks the form as failed on rejection', async () => {
    h.mock(authService, 'submitTwoFactorChallenge').mockRejectedValue('Unauthorized')
    h.render(Component, { props: { loginToken: 'login-token-abc' } })

    await h.type(screen.getByPlaceholderText('Authentication code'), '000000')
    await h.user.click(screen.getByTestId('submit'))
    await h.tick()

    expect(screen.getByTestId('two-factor-challenge-form').classList.contains('error')).toBe(true)
  })
})
