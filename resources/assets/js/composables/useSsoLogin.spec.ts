import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useSsoLogin } from './useSsoLogin'

const openPopupMock = vi.fn()
const token: CompositeToken = { token: 'api-token', 'audio-token': 'audio-token' }

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  openPopup: (...args: any[]) => openPopupMock(...args),
}))

describe('useSsoLogin', () => {
  createHarness()

  const openForeignWindow = () => {
    const frame = document.createElement('iframe')
    document.body.appendChild(frame)

    return frame.contentWindow!
  }

  const post = (data: unknown, origin: string, source: Window) => {
    window.dispatchEvent(new MessageEvent('message', { data, origin, source }))
  }

  const start = (onToken = vi.fn()) => {
    const popup = openForeignWindow()
    openPopupMock.mockReturnValue(popup)

    const { startSsoLogin } = useSsoLogin()
    startSsoLogin('/auth/oidc/redirect', 'OpenID Login', onToken)

    return { onToken, popup }
  }

  it('opens the popup at the redirect URL', () => {
    const { popup } = start()

    expect(openPopupMock).toHaveBeenCalledWith('/auth/oidc/redirect', 'OpenID Login', 768, 640, window)

    post(token, window.location.origin, popup)
  })

  it('throws when the popup cannot be opened', () => {
    openPopupMock.mockReturnValue(null)

    const { startSsoLogin } = useSsoLogin()

    expect(() => startSsoLogin('/auth/oidc/redirect', 'OpenID Login', vi.fn())).toThrow()
  })

  it('accepts a composite token posted by the popup it opened', () => {
    const { onToken, popup } = start()

    post(token, window.location.origin, popup)

    expect(onToken).toHaveBeenCalledWith(token)
  })

  it('ignores a token posted from a foreign origin', () => {
    const { onToken, popup } = start()

    post(token, 'https://evil.test', popup)

    expect(onToken).not.toHaveBeenCalled()

    post(token, window.location.origin, popup)
    expect(onToken).toHaveBeenCalledWith(token)
  })

  it('ignores a token posted by a window it did not open', () => {
    const { onToken, popup } = start()

    post(token, window.location.origin, openForeignWindow())

    expect(onToken).not.toHaveBeenCalled()

    post(token, window.location.origin, popup)
    expect(onToken).toHaveBeenCalledWith(token)
  })

  it('ignores an invalid composite token payload', () => {
    const { onToken, popup } = start()

    post({ token: 'api-token' }, window.location.origin, popup)

    expect(onToken).not.toHaveBeenCalled()

    post(token, window.location.origin, popup)
    expect(onToken).toHaveBeenCalledWith(token)
  })

  it('stops listening once a token has been accepted', () => {
    const { onToken, popup } = start()

    post(token, window.location.origin, popup)
    post({ token: 'second-api-token', 'audio-token': 'second-audio-token' }, window.location.origin, popup)

    expect(onToken).toHaveBeenCalledOnce()
  })
})
