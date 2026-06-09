import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { copyText } from '@/utils/helpers'
import Component from './TwoFactorRecoveryCodes.vue'

vi.mock('@/utils/helpers', async original => {
  const mod = await original<typeof import('@/utils/helpers')>()
  return { ...mod, copyText: vi.fn() }
})

describe('twoFactorRecoveryCodes.vue', () => {
  const h = createHarness()

  it('lists the codes', () => {
    h.render(Component, { props: { codes: ['AAAA BBBB', 'CCCC DDDD'] } })

    screen.getByText('AAAA BBBB')
    screen.getByText('CCCC DDDD')
  })

  it('copies the codes to the clipboard', async () => {
    const toastMock = h.mock(MessageToasterStub.value, 'success')

    h.render(Component, { props: { codes: ['AAAA BBBB', 'CCCC DDDD'] } })

    await h.user.click(screen.getByRole('button', { name: 'Copy recovery codes' }))

    expect(copyText).toHaveBeenCalledWith('AAAA BBBB\nCCCC DDDD')
    expect(toastMock).toHaveBeenCalledWith('Recovery codes copied.')
  })

  it('emits dismiss when acknowledged', async () => {
    const { emitted } = h.render(Component, { props: { codes: ['AAAA BBBB'] } })

    await h.user.click(screen.getByRole('button', { name: "I've saved them" }))

    expect(emitted().dismiss).toBeTruthy()
  })
})
