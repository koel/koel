import { defineComponent } from 'vue'
import { screen } from '@testing-library/vue'
import { afterEach, describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import { MessageToasterStub } from '@/__tests__/stubs'
import Component from './SsoLoginOptions.vue'

const token = { 'audio-token': 'audio-token', token: 'api-token' }

const GoogleLoginButtonStub = defineComponent({
  emits: ['success', 'error'],
  template: `
    <span>
      <button data-testid="sso-success" @click="$emit('success', token)">ok</button>
      <button data-testid="sso-error" @click="$emit('error', 'boom')">fail</button>
    </span>
  `,
  setup: () => ({ token }),
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

  it('renders the configured provider buttons', () => {
    window.KOEL.sso_providers = ['Google']
    renderWithGoogle()

    screen.getByTestId('sso-success')
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
})
