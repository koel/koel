import { screen } from '@testing-library/vue'
import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { PASSKEY_ADDRESS_REJECTED_MESSAGE, passkeyService } from '@/services/passkeyService'
import Component from './PasskeyLoginButton.vue'

describe('passkeyLoginButton.vue', () => {
  const h = createHarness({
    beforeEach: () => vi.stubGlobal('PublicKeyCredential', { parseRequestOptionsFromJSON: vi.fn() }),
  })

  afterEach(() => vi.unstubAllGlobals())

  it('logs in with a passkey', async () => {
    h.mock(passkeyService, 'logIn').mockResolvedValue(undefined)
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByRole('button'))

    expect(emitted().loggedIn).toBeTruthy()
  })

  it('stays quiet when the passkey prompt is dismissed', async () => {
    h.mock(passkeyService, 'logIn').mockRejectedValue(new DOMException('Dismissed', 'NotAllowedError'))
    const errorMock = h.mock(MessageToasterStub.value, 'error')
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByRole('button'))

    expect(errorMock).not.toHaveBeenCalled()
    expect(emitted().loggedIn).toBeUndefined()
  })

  it('reports a failed passkey login', async () => {
    h.mock(passkeyService, 'logIn').mockRejectedValue(new Error('Invalid credentials'))
    const errorMock = h.mock(MessageToasterStub.value, 'error')
    h.render(Component)

    await h.user.click(screen.getByRole('button'))

    expect(errorMock).toHaveBeenCalled()
  })

  it('is hidden when the browser does not support passkeys', () => {
    vi.stubGlobal('PublicKeyCredential', undefined)
    h.render(Component)

    expect(screen.queryByRole('button')).toBeNull()
  })

  it('explains when the browser rejects the address', async () => {
    h.mock(passkeyService, 'logIn').mockRejectedValue(new DOMException('Invalid domain', 'SecurityError'))
    const errorMock = h.mock(MessageToasterStub.value, 'error')
    h.render(Component)

    await h.user.click(screen.getByRole('button'))

    expect(errorMock).toHaveBeenCalledWith(PASSKEY_ADDRESS_REJECTED_MESSAGE)
  })
})
