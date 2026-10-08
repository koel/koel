import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { DialogBoxStub } from '@/__tests__/stubs'
import { passkeyService } from '@/services/passkeyService'
import Component from './AddPasskeyForm.vue'

describe('addPasskeyForm.vue', () => {
  const h = createHarness()

  it('adds a passkey under the trimmed name', async () => {
    const passkey = { type: 'passkeys', id: 1, name: 'MacBook' }
    const addMock = h.mock(passkeyService, 'add').mockResolvedValue(passkey)
    const { emitted } = h.render(Component)

    await h.user.type(screen.getByRole('textbox'), '  MacBook  ')
    await h.user.click(screen.getByRole('button', { name: 'Add' }))

    expect(addMock).toHaveBeenCalledWith('MacBook')
    expect(emitted().added?.[0]).toEqual([passkey])
  })

  it('stays quiet when the passkey prompt is dismissed', async () => {
    h.mock(passkeyService, 'add').mockRejectedValue(new DOMException('Dismissed', 'NotAllowedError'))
    const errorMock = h.mock(DialogBoxStub.value, 'error')
    const { emitted } = h.render(Component)

    await h.user.type(screen.getByRole('textbox'), 'MacBook')
    await h.user.click(screen.getByRole('button', { name: 'Add' }))

    expect(errorMock).not.toHaveBeenCalled()
    expect(emitted().added).toBeUndefined()
  })
})
