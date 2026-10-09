import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { DialogBoxStub } from '@/__tests__/stubs'
import { PASSKEY_ADDRESS_REJECTED_MESSAGE, passkeyService } from '@/services/passkeyService'
import Component from './AddPasskeyForm.vue'

describe('addPasskeyForm.vue', () => {
  const h = createHarness()

  const passkey = { type: 'passkeys', id: 1, name: 'MacBook' }

  const renderComponent = (hasPasskeys = false) => h.render(Component, { props: { hasPasskeys } })

  const fillIn = async (name: string, password?: string) => {
    await h.user.type(screen.getByPlaceholderText('MacBook, YubiKey…'), name)

    if (password) {
      await h.user.type(screen.getByLabelText('Your password'), password)
    }

    await h.user.click(screen.getByRole('button', { name: 'Add' }))
  }

  it('adds a passkey under the trimmed name after confirming the password', async () => {
    h.actingAsUser()
    const addMock = h.mock(passkeyService, 'add').mockResolvedValue(passkey)
    const { emitted } = renderComponent()

    await fillIn('  MacBook  ', 'secret')

    expect(addMock).toHaveBeenCalledWith('MacBook', { password: 'secret', code: '' })
    expect(emitted().added?.[0]).toEqual([passkey])
  })

  it('confirms with an existing passkey when there is one', async () => {
    h.actingAsUser()
    const proof = { credential: { id: 'credential-id' } }
    h.mock(passkeyService, 'confirmIdentity').mockResolvedValue(proof)
    const addMock = h.mock(passkeyService, 'add').mockResolvedValue(passkey)
    renderComponent(true)

    await fillIn('MacBook')

    expect(addMock).toHaveBeenCalledWith('MacBook', proof)
  })

  it('needs no proof from a single sign-on user without passkeys', async () => {
    h.actingAsUser(h.factory('user').state('current').make({ sso_provider: 'Google' }) as CurrentUser)
    const addMock = h.mock(passkeyService, 'add').mockResolvedValue(passkey)
    renderComponent()

    await fillIn('MacBook')

    expect(addMock).toHaveBeenCalledWith('MacBook', {})
  })

  it('stays quiet when the passkey prompt is dismissed', async () => {
    h.actingAsUser()
    h.mock(passkeyService, 'add').mockRejectedValue(new DOMException('Dismissed', 'NotAllowedError'))
    const errorMock = h.mock(DialogBoxStub.value, 'error')
    const { emitted } = renderComponent()

    await fillIn('MacBook', 'secret')

    expect(errorMock).not.toHaveBeenCalled()
    expect(emitted().added).toBeUndefined()
  })

  it('explains when the browser rejects the address', async () => {
    h.actingAsUser()
    h.mock(passkeyService, 'add').mockRejectedValue(new DOMException('Invalid domain', 'SecurityError'))
    const errorMock = h.mock(DialogBoxStub.value, 'error')
    renderComponent()

    await fillIn('MacBook', 'secret')

    expect(errorMock).toHaveBeenCalledWith(PASSKEY_ADDRESS_REJECTED_MESSAGE)
  })
})
