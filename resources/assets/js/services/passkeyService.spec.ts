import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import { http } from '@/services/http'
import { isPasskeySupported, passkeyService } from '@/services/passkeyService'

class FakePublicKeyCredential {
  static parseRequestOptionsFromJSON = vi.fn((options: unknown) => ({ parsed: options }))
  static parseCreationOptionsFromJSON = vi.fn((options: unknown) => ({ parsed: options }))

  toJSON() {
    return { id: 'credential-id' }
  }
}

describe('passkeyService', () => {
  const h = createHarness({
    beforeEach: () => vi.stubGlobal('PublicKeyCredential', FakePublicKeyCredential),
  })

  afterEach(() => vi.unstubAllGlobals())

  const stubCredentials = () => {
    const credentials = {
      get: vi.fn().mockResolvedValue(new FakePublicKeyCredential()),
      create: vi.fn().mockResolvedValue(new FakePublicKeyCredential()),
    }

    h.setReadOnlyProperty(navigator, 'credentials', credentials)

    return credentials
  }

  it('logs in with a passkey', async () => {
    const credentials = stubCredentials()
    const compositeToken = { token: 'api-token', 'audio-token': 'audio-token' }
    h.mock(http, 'get').mockResolvedValue({ options: { challenge: 'abc' }, login_token: 'login-token' })
    const postMock = h.mock(http, 'post').mockResolvedValue(compositeToken)
    const setTokensMock = h.mock(authService, 'setTokensUsingCompositeToken')
    h.mock(authService, 'maybeRedirect')

    await passkeyService.logIn()

    expect(credentials.get).toHaveBeenCalledWith({ publicKey: { parsed: { challenge: 'abc' } } })
    expect(postMock).toHaveBeenCalledWith('me/passkey-login', {
      login_token: 'login-token',
      credential: { id: 'credential-id' },
    })
    expect(setTokensMock).toHaveBeenCalledWith(compositeToken)
  })

  it('refuses a login when the browser returns no passkey', async () => {
    stubCredentials().get.mockResolvedValue(null)
    h.mock(http, 'get').mockResolvedValue({ options: {}, login_token: 'login-token' })
    const postMock = h.mock(http, 'post')

    await expect(passkeyService.logIn()).rejects.toThrow(TypeError)
    expect(postMock).not.toHaveBeenCalled()
  })

  it('adds a passkey', async () => {
    const credentials = stubCredentials()
    h.mock(http, 'get').mockResolvedValue({ challenge: 'abc' })
    const postMock = h.mock(http, 'post').mockResolvedValue({ id: 1, name: 'MacBook' })

    await passkeyService.add('MacBook')

    expect(credentials.create).toHaveBeenCalledWith({ publicKey: { parsed: { challenge: 'abc' } } })
    expect(postMock).toHaveBeenCalledWith('me/passkeys', { name: 'MacBook', credential: { id: 'credential-id' } })
  })

  it('reports no passkey support without the browser API', () => {
    vi.stubGlobal('PublicKeyCredential', undefined)

    expect(isPasskeySupported()).toBe(false)
  })
})
