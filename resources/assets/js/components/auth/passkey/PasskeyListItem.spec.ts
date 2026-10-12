import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { DialogBoxStub } from '@/__tests__/stubs'
import { passkeyService } from '@/services/passkeyService'
import Component from './PasskeyListItem.vue'

describe('passkeyListItem.vue', () => {
  const h = createHarness()

  const passkey: Passkey = {
    type: 'passkeys',
    id: 1,
    name: 'MacBook',
    authenticator: null,
    last_used_at: null,
    created_at: '2026-10-01T00:00:00Z',
  }

  const renderComponent = () => h.render(Component, { props: { passkey } })

  it('removes the passkey once confirmed', async () => {
    const removeMock = h.mock(passkeyService, 'remove').mockResolvedValue(undefined)
    const { emitted } = renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    expect(removeMock).toHaveBeenCalledWith(passkey)
    expect(emitted().removed?.[0]).toEqual([passkey])
  })

  it('keeps the passkey when removal is not confirmed', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(false)
    const removeMock = h.mock(passkeyService, 'remove')
    const { emitted } = renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    expect(removeMock).not.toHaveBeenCalled()
    expect(emitted().removed).toBeUndefined()
  })
})
