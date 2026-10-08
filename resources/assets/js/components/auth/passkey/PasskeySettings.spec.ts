import { screen, waitFor } from '@testing-library/vue'
import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { passkeyService } from '@/services/passkeyService'
import Component from './PasskeySettings.vue'

describe('passkeySettings.vue', () => {
  const h = createHarness({
    beforeEach: () => vi.stubGlobal('PublicKeyCredential', { parseRequestOptionsFromJSON: vi.fn() }),
  })

  afterEach(() => vi.unstubAllGlobals())

  const makePasskey = (overrides: Partial<Passkey> = {}): Passkey => ({
    type: 'passkeys',
    id: 1,
    name: 'MacBook',
    authenticator: 'iCloud Keychain',
    last_used_at: null,
    created_at: '2026-10-01T00:00:00Z',
    ...overrides,
  })

  const listedNames = () =>
    Array.from(screen.queryByTestId('passkey-list')?.querySelectorAll('li') ?? []).map(
      item => item.querySelector('p')?.textContent,
    )

  it('lists the passkeys', async () => {
    h.mock(passkeyService, 'fetchAll').mockResolvedValue([makePasskey(), makePasskey({ id: 2, name: 'YubiKey' })])
    h.render(Component)

    await waitFor(() => expect(listedNames()).toEqual(['MacBook', 'YubiKey']))
  })

  it('removes a passkey', async () => {
    const passkey = makePasskey()
    h.mock(passkeyService, 'fetchAll').mockResolvedValue([passkey])
    const removeMock = h.mock(passkeyService, 'remove').mockResolvedValue(undefined)
    h.render(Component)

    await h.user.click(await screen.findByRole('button', { name: 'Remove' }))

    expect(removeMock).toHaveBeenCalledWith(passkey)
    await waitFor(() => expect(listedNames()).toEqual([]))
  })

  it('adds a passkey to the list once it is created', async () => {
    h.mock(passkeyService, 'fetchAll').mockResolvedValue([])
    h.mock(passkeyService, 'add').mockResolvedValue(makePasskey({ id: 3, name: 'Pixel' }))
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Add a Passkey' }))
    await h.user.type(screen.getByRole('textbox'), 'Pixel')
    await h.user.click(screen.getByRole('button', { name: 'Add' }))

    await waitFor(() => expect(listedNames()).toEqual(['Pixel']))
  })

  it('explains when the browser does not support passkeys', () => {
    vi.stubGlobal('PublicKeyCredential', undefined)
    h.mock(passkeyService, 'fetchAll').mockResolvedValue([])
    h.render(Component)

    screen.getByTestId('passkeys-unsupported')
  })
})
