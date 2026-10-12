import { defineComponent } from 'vue'
import { screen } from '@testing-library/vue'
import { afterEach, describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import { MessageToasterStub } from '@/__tests__/stubs'
import Component from './SsoLoginOptions.vue'

const token = { 'audio-token': 'audio-token', token: 'api-token' }
const challenge = { two_factor: true, login_token: 'login-token' }

const GoogleLoginButtonStub = defineComponent({
  emits: ['success', 'error'],
  template: `
    <span>
      <button data-testid="sso-success" @click="$emit('success', token)">ok</button>
      <button data-testid="sso-error" @click="$emit('error', 'boom')">fail</button>
      <button data-testid="sso-two-factor" @click="$emit('success', challenge)">2fa</button>
    </span>
  `,
  setup: () => ({ token, challenge }),
})

const renderWithGoogle = () =>
  h.render(Component, {
    global: {
      stubs: { GoogleLoginButton: GoogleLoginButtonStub },
    },
  })

const h = createHarness({
  authenticated: false,
})

describe('ssoLoginOptions.vue', () => {
  afterEach(() => (window.KOEL.sso_providers = []))

  it('renders nothing when no providers are configured', () => {
    const { container } = h.render(Component)

    expect(container.querySelector('div')).toBeNull()
  })

  it('sets tokens, reconciles redirects and emits loggedIn on success', async () => {
    window.KOEL.sso_providers = ['Google']
    const setTokensMock = h.mock(authService, 'setTokensUsingCompositeToken')
    const redirectMock = h.mock(authService, 'maybeRedirect')
    const { emitted } = renderWithGoogle()

    await h.user.click(screen.getByTestId('sso-success'))

    expect(setTokensMock).toHaveBeenCalledWith(token)
    expect(redirectMock).toHaveBeenCalled()
    expect(emitted().loggedIn).toBeTruthy()
  })

  it('toasts on error without emitting loggedIn', async () => {
    window.KOEL.sso_providers = ['Google']
    const toastMock = h.mock(MessageToasterStub.value, 'error')
    const { emitted } = renderWithGoogle()

    await h.user.click(screen.getByTestId('sso-error'))

    expect(toastMock).toHaveBeenCalledWith('Login failed. Please try again.')
    expect(emitted().loggedIn).toBeFalsy()
  })

  it('asks for the two-factor code instead of logging in when a challenge comes back', async () => {
    window.KOEL.sso_providers = ['Google']
    const setTokensMock = h.mock(authService, 'setTokensUsingCompositeToken')
    const { emitted } = renderWithGoogle()

    await h.user.click(screen.getByTestId('sso-two-factor'))

    expect(setTokensMock).not.toHaveBeenCalled()
    expect(emitted().twoFactorRequired).toEqual([['login-token']])
    expect(emitted().loggedIn).toBeFalsy()
  })
})
