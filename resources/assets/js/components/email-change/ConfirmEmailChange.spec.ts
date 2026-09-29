import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import Component from './ConfirmEmailChange.vue'

describe('confirmEmailChange.vue', () => {
  const h = createHarness()

  const signedPath = 'email-change/confirm/1?current=abc&email=new%40koel.test&expires=1&token=xyz&signature=sig'

  it('confirms the change', async () => {
    const confirmMock = h.mock(authService, 'confirmEmailChange').mockResolvedValue(undefined)

    h.visit(
      '/email-change/ZW1haWwtY2hhbmdlL2NvbmZpcm0vMT9jdXJyZW50PWFiYyZlbWFpbD1uZXclNDBrb2VsLnRlc3QmZXhwaXJlcz0xJnRva2VuPXh5eiZzaWduYXR1cmU9c2ln',
    ).render(Component)
    await h.user.click(screen.getByRole('button', { name: 'Confirm' }))

    expect(confirmMock).toHaveBeenCalledWith(signedPath)
    screen.getByTestId('changed')
  })

  it('stays unchanged when the confirmation fails', async () => {
    h.mock(authService, 'confirmEmailChange').mockRejectedValue(new Error('Forbidden'))

    h.visit(
      '/email-change/ZW1haWwtY2hhbmdlL2NvbmZpcm0vMT9jdXJyZW50PWFiYyZlbWFpbD1uZXclNDBrb2VsLnRlc3QmZXhwaXJlcz0xJnRva2VuPXh5eiZzaWduYXR1cmU9c2ln',
    ).render(Component)
    await h.user.click(screen.getByRole('button', { name: 'Confirm' }))

    expect(screen.queryByTestId('changed')).toBeNull()
  })

  it('shows nothing for an unreadable link', () => {
    h.visit('/email-change/bm9wZQ==').render(Component)

    expect(screen.queryByRole('button', { name: 'Confirm' })).toBeNull()
  })
})
