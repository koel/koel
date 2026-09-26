import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import { logger } from '@/utils/logger'
import Component from './CredentialsLoginForm.vue'

describe('credentialsLoginForm.vue', () => {
  const h = createHarness({
    authenticated: false,
  })

  const fillAndSubmit = async () => {
    await h.type(screen.getByPlaceholderText('Your email address'), 'john@doe.com')
    await h.type(screen.getByPlaceholderText('Your password'), 'secret')
    await h.user.click(screen.getByTestId('submit'))
  }

  it('fills in the email from the login hint, focuses the password and drops the hint from the address', async () => {
    window.history.replaceState(null, '', '/?login_hint=john%40doe.com#/home')
    h.render(Component)

    expect(screen.getByPlaceholderText<HTMLInputElement>('Your email address').value).toBe('john@doe.com')
    expect(screen.getByPlaceholderText<HTMLInputElement>('Your email address').readOnly).toBe(false)
    expect(screen.getByPlaceholderText('Your email address').hasAttribute('autofocus')).toBe(false)
    expect(screen.getByPlaceholderText('Your password').hasAttribute('autofocus')).toBe(true)
    expect(window.location.search).toBe('')
    expect(window.location.hash).toBe('#/home')

    window.history.replaceState(null, '', '/')
  })

  it('drops an empty login hint from the address and focuses the email', async () => {
    window.history.replaceState(null, '', '/?login_hint=#/home')
    h.render(Component)

    expect(screen.getByPlaceholderText('Your email address').hasAttribute('autofocus')).toBe(true)
    expect(window.location.search).toBe('')

    window.history.replaceState(null, '', '/')
  })

  it('emits loggedIn on successful login', async () => {
    const loginMock = h.mock(authService, 'login')
    const { emitted } = h.render(Component)

    await fillAndSubmit()

    expect(loginMock).toHaveBeenCalledWith('john@doe.com', 'secret')
    expect(emitted().loggedIn).toBeTruthy()
  })

  it('emits twoFactorRequired when a challenge is returned', async () => {
    h.mock(authService, 'login').mockResolvedValue({ login_token: 'login-token-abc' })
    const { emitted } = h.render(Component)

    await fillAndSubmit()
    await h.tick()

    expect(emitted().twoFactorRequired?.[0]).toEqual(['login-token-abc'])
    expect(emitted().loggedIn).toBeFalsy()
  })

  it('enters the error state on failed login', async () => {
    h.mock(authService, 'login').mockRejectedValue('Unauthenticated')
    const logMock = h.mock(logger, 'error')
    const { emitted } = h.render(Component)

    await fillAndSubmit()
    await h.tick()

    expect(emitted().loggedIn).toBeFalsy()
    expect(screen.getByTestId('login-form').classList.contains('error')).toBe(true)
    expect(logMock).toHaveBeenCalledWith('Unauthenticated')
  })

  it('emits forgotPassword when the link is clicked', async () => {
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByText('Forgot password?'))

    expect(emitted().forgotPassword).toBeTruthy()
  })

  it('hides the forgot-password link when the mailer is not configured', () => {
    window.KOEL.mailer_configured = false
    h.render(Component)

    expect(screen.queryByText('Forgot password?')).toBeNull()
    window.KOEL.mailer_configured = true
  })
})
