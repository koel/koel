import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import Component from './Auth.vue'

describe('auth.vue', () => {
  const h = createHarness({
    authenticated: false,
  })

  const renderAndSubmitCredentials = async () => {
    const rendered = h.render(Component)

    await h.type(await screen.findByPlaceholderText('Your email address'), 'john@doe.com')
    await h.type(screen.getByPlaceholderText('Your password'), 'secret')
    await h.user.click(screen.getByTestId('submit'))

    return rendered
  }

  it('renders the credentials form by default', async () => {
    h.render(Component)

    await screen.findByTestId('login-form')
  })

  it('re-emits loggedIn from the credentials form', async () => {
    h.mock(authService, 'login')
    const { emitted } = await renderAndSubmitCredentials()
    await h.tick()

    expect(emitted().loggedIn).toBeTruthy()
  })

  it('shows the two-factor challenge when a challenge is required', async () => {
    h.mock(authService, 'login').mockResolvedValue({ login_token: 'login-token-abc' })
    await renderAndSubmitCredentials()

    await screen.findByTestId('two-factor-challenge-form')
    expect(screen.queryByTestId('login-form')).toBeNull()
  })

  it('returns to the credentials form when the two-factor challenge is cancelled', async () => {
    h.mock(authService, 'login').mockResolvedValue({ login_token: 'login-token-abc' })
    await renderAndSubmitCredentials()

    await h.user.click(await screen.findByTestId('cancel'))

    await screen.findByTestId('login-form')
  })

  it('shows the forgot-password form', async () => {
    h.render(Component)

    await h.user.click(await screen.findByText('Forgot password?'))

    await screen.findByTestId('forgot-password-form')
    expect(screen.queryByTestId('login-form')).toBeNull()
  })

  it('returns to the credentials form when forgot-password is cancelled', async () => {
    h.render(Component)

    await h.user.click(await screen.findByText('Forgot password?'))
    await h.user.click(await screen.findByText('Cancel'))

    await screen.findByTestId('login-form')
  })

  it('shows the SSO login options', async () => {
    window.KOEL.sso_providers = ['Google']

    h.render(Component, {
      global: {
        stubs: {
          GoogleLoginButton: h.stub('google-login-button'),
        },
      },
    })

    await waitFor(() => screen.getByTestId('google-login-button'))

    window.KOEL.sso_providers = []
  })
})
