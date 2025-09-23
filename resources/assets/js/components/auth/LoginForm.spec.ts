import { screen, waitFor } from '@testing-library/vue'
import type { Mock } from 'vitest'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import { logger } from '@/utils/logger'
import Component from './LoginForm.vue'

describe('loginForm.vue', () => {
  const h = createHarness({
    authenticated: false,
  })

  const submitForm = async (loginMock: Mock) => {
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
    window.MAILER_CONFIGURED = false
    h.render(Component)

    expect(screen.queryByText('Forgot password?')).toBeNull()
    window.MAILER_CONFIGURED = true
  })

  it('shows Google login button', async () => {
    window.SSO_PROVIDERS = ['Google']

    h.render(Component, {
      global: {
        stubs: {
          GoogleLoginButton: h.stub('google-login-button'),
        },
      },
    })

    screen.getByTestId('google-login-button')

    window.SSO_PROVIDERS = []
  })
})
