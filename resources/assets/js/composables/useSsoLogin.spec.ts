import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useSsoLogin } from './useSsoLogin'

const openPopupMock = vi.fn()

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  openPopup: (...args: any[]) => openPopupMock(...args),
}))

describe('useSsoLogin', () => {
  createHarness()

  const postToOpener = (origin: string, token: string) => {
    window.onmessage!(new MessageEvent('message', { data: token, origin }))
  }

  it('opens the popup at the redirect URL', () => {
    const { startSsoLogin } = useSsoLogin()
    startSsoLogin('/auth/oidc/redirect', 'OpenID Login', vi.fn())

    expect(openPopupMock).toHaveBeenCalledWith('/auth/oidc/redirect', 'OpenID Login', 768, 640, window)
  })

  it('accepts a token posted from Koel’s own origin', () => {
    const onToken = vi.fn()
    const { startSsoLogin } = useSsoLogin()
    startSsoLogin('/auth/oidc/redirect', 'OpenID Login', onToken)

    postToOpener(window.location.origin, 'the-token')

    expect(onToken).toHaveBeenCalledWith('the-token')
  })

  it('ignores a token posted from a foreign origin', () => {
    const onToken = vi.fn()
    const { startSsoLogin } = useSsoLogin()
    startSsoLogin('/auth/oidc/redirect', 'OpenID Login', onToken)

    postToOpener('https://evil.test', 'forged-token')

    expect(onToken).not.toHaveBeenCalled()
  })
})
