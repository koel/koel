import { describe, expect, it } from 'vite-plus/test'
import { screen, within } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import Component from './TwoFactorChallengeForm.vue'

describe('twoFactorChallengeForm.vue', () => {
  const h = createHarness({ authenticated: false })

  const typeTotp = async (digits: string) => {
    const boxes = within(screen.getByTestId('one-time-code-input')).getAllByRole<HTMLInputElement>('textbox')

    for (let i = 0; i < digits.length; i++) {
      await h.type(boxes[i], digits[i])
    }
  }

  it('submits a 6-digit TOTP code via the Verify button', async () => {
    const challengeMock = h.mock(authService, 'submitTwoFactorChallenge')
    const { emitted } = h.render(Component, { props: { loginToken: 'login-token-abc' } })

    await typeTotp('123456')
    await h.user.click(screen.getByTestId('submit'))

    expect(challengeMock).toHaveBeenCalledWith('login-token-abc', '123456')
    expect(emitted().verified).toBeTruthy()
  })

  it('auto-submits when the 6th digit is entered', async () => {
    const challengeMock = h.mock(authService, 'submitTwoFactorChallenge')
    const { emitted } = h.render(Component, { props: { loginToken: 'login-token-abc' } })

    await typeTotp('123456')

    expect(challengeMock).toHaveBeenCalledWith('login-token-abc', '123456')
    expect(emitted().verified).toBeTruthy()
  })

  it('switches to recovery-code mode and submits a recovery code', async () => {
    const challengeMock = h.mock(authService, 'submitTwoFactorChallenge')
    const { emitted } = h.render(Component, { props: { loginToken: 'login-token-abc' } })

    await h.user.click(screen.getByTestId('use-recovery-code'))

    expect(screen.queryByTestId('one-time-code-input')).toBeNull()
    const recoveryField = screen.getByTestId('recovery-code-input')

    await h.type(recoveryField, 'AAAA BBBB CCCC DDDD EEEE FFFF GGGG HHHH')
    await h.user.click(screen.getByTestId('submit'))

    expect(challengeMock).toHaveBeenCalledWith('login-token-abc', 'AAAA BBBB CCCC DDDD EEEE FFFF GGGG HHHH')
    expect(emitted().verified).toBeTruthy()
  })

  it('toggles back from recovery mode to TOTP mode', async () => {
    h.render(Component, { props: { loginToken: 'login-token-abc' } })

    await h.user.click(screen.getByTestId('use-recovery-code'))
    await h.user.click(screen.getByTestId('use-totp-code'))

    screen.getByTestId('one-time-code-input')
    expect(screen.queryByTestId('recovery-code-input')).toBeNull()
  })

  it('emits cancel when Back to login is clicked', async () => {
    const { emitted } = h.render(Component, { props: { loginToken: 'login-token-abc' } })

    await h.user.click(screen.getByTestId('cancel'))

    expect(emitted().cancel).toBeTruthy()
  })

  it('marks the form as failed and clears the code on TOTP rejection', async () => {
    h.mock(authService, 'submitTwoFactorChallenge').mockRejectedValue('Unauthorized')
    h.render(Component, { props: { loginToken: 'login-token-abc' } })

    await typeTotp('000000')
    await h.tick()

    expect(screen.getByTestId('two-factor-challenge-form').classList.contains('error')).toBe(true)
    const boxes = within(screen.getByTestId('one-time-code-input')).getAllByRole<HTMLInputElement>('textbox')
    expect(boxes.every(b => b.value === '')).toBe(true)
  })

  it('keeps the recovery code on rejection so the user can fix a typo', async () => {
    h.mock(authService, 'submitTwoFactorChallenge').mockRejectedValue('Unauthorized')
    h.render(Component, { props: { loginToken: 'login-token-abc' } })

    await h.user.click(screen.getByTestId('use-recovery-code'))
    const recoveryField = screen.getByTestId<HTMLInputElement>('recovery-code-input')
    await h.type(recoveryField, 'AAAA BBBB CCCC DDDD EEEE FFFF GGGG HHHH')
    await h.user.click(screen.getByTestId('submit'))
    await h.tick()

    expect(screen.getByTestId('two-factor-challenge-form').classList.contains('error')).toBe(true)
    expect(recoveryField.value).toBe('AAAA BBBB CCCC DDDD EEEE FFFF GGGG HHHH')
  })
})
