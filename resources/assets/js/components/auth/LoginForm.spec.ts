import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import { logger } from '@/utils/logger'
import Component from './LoginForm.vue'

describe('loginForm.vue', () => {
  const h = createHarness({
    authenticated: false,
  })

  const submitForm = async (loginMock: ReturnType<typeof h.mock>) => {
    const rendered = h.render(Component)

    await h.type(screen.getByPlaceholderText('Your email address'), 'john@doe.com')
    await h.type(screen.getByPlaceholderText('Your password'), 'secret')
    await h.user.click(screen.getByTestId('submit'))

    expect(loginMock).toHaveBeenCalledWith('john@doe.com', 'secret')

    return rendered
  }

  it('renders', () => expect(h.render(Component).html()).toMatchSnapshot())

  it('logs in', async () => {
    expect((await submitForm(h.mock(authService, 'login'))).emitted().loggedin).toBeTruthy()
  })

  it('fails to log in', async () => {
    const mock = h.mock(authService, 'login').mockRejectedValue('Unauthenticated')
    const logMock = h.mock(logger, 'error')
    const { emitted } = await submitForm(mock)
    await h.tick()

    expect(emitted().loggedin).toBeFalsy()
    expect(screen.getByTestId('login-form').classList.contains('error')).toBe(true)
    expect(logMock).toHaveBeenCalledWith('Unauthenticated')
  })

  it('shows forgot password form', async () => {
    h.render(Component)
    await h.user.click(screen.getByText('Forgot password?'))

    await waitFor(() => screen.getByTestId('forgot-password-form'))
  })

  it('does not show forgot password form if mailer is not configure', async () => {
    window.KOEL.mailer_configured = false
    h.render(Component)

    expect(screen.queryByText('Forgot password?')).toBeNull()
    window.KOEL.mailer_configured = true
  })

  it('shows Google login button', async () => {
    window.KOEL.sso_providers = ['Google']

    h.render(Component, {
      global: {
        stubs: {
          GoogleLoginButton: h.stub('google-login-button'),
        },
      },
    })

    screen.getByTestId('google-login-button')

    window.KOEL.sso_providers = []
  })
})
