import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import Component from './TwoFactorEnrollment.vue'

describe('twoFactorEnrollment.vue', () => {
  const h = createHarness()

  it('starts enrollment on mount and renders the QR code', async () => {
    const enrollMock = h.mock(authService, 'enrollTwoFactor').mockResolvedValue({
      provisioning_uri: 'otpauth://totp/Koel:foo@bar?secret=ABC&issuer=Koel',
    })

    h.render(Component)

    await waitFor(() => screen.getByAltText('Two-factor authentication QR code'))
    expect(enrollMock).toHaveBeenCalled()
  })

  it('emits enrolled with the recovery codes on a valid confirmation', async () => {
    h.mock(authService, 'enrollTwoFactor').mockResolvedValue({
      provisioning_uri: 'otpauth://totp/Koel:foo@bar?secret=ABC&issuer=Koel',
    })
    h.mock(authService, 'confirmTwoFactor').mockResolvedValue({
      recovery_codes: ['AAAA BBBB', 'CCCC DDDD'],
    })

    const { emitted } = h.render(Component)
    await waitFor(() => screen.getByAltText('Two-factor authentication QR code'))

    await h.type(screen.getByPlaceholderText('123 456'), '123456')
    await h.user.click(screen.getByRole('button', { name: 'Confirm' }))

    await waitFor(() => expect(emitted().enrolled).toEqual([[['AAAA BBBB', 'CCCC DDDD']]]))
  })

  it('emits cancel on Cancel click', async () => {
    h.mock(authService, 'enrollTwoFactor').mockResolvedValue({
      provisioning_uri: 'otpauth://totp/Koel:foo@bar?secret=ABC&issuer=Koel',
    })

    const { emitted } = h.render(Component)
    await waitFor(() => screen.getByAltText('Two-factor authentication QR code'))

    await h.user.click(screen.getByRole('button', { name: 'Cancel' }))

    expect(emitted().cancel).toBeTruthy()
  })

  it('cancels and toasts if enroll request fails', async () => {
    h.mock(authService, 'enrollTwoFactor').mockRejectedValue(new Error('boom'))

    const { emitted } = h.render(Component)

    await waitFor(() => expect(emitted().cancel).toBeTruthy())
  })
})
