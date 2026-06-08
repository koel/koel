import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import Component from './TwoFactorAuthSettings.vue'

describe('twoFactorAuthSettings.vue', () => {
  const h = createHarness()

  const renderEnabled = () =>
    h.actingAsUser(h.factory('user').make({ two_factor: true }) as CurrentUser).render(Component)

  const renderDisabled = () =>
    h.actingAsUser(h.factory('user').make({ two_factor: false }) as CurrentUser).render(Component)

  it('shows the enable button when two-factor is off', () => {
    renderDisabled()
    screen.getByRole('button', { name: 'Enable Two-Factor Authentication' })
  })

  it('starts setup and renders the QR code on enable', async () => {
    h.mock(authService, 'setupTwoFactor').mockResolvedValue({
      provisioning_uri: 'otpauth://totp/Koel:foo@bar?secret=ABC&issuer=Koel',
    })
    renderDisabled()

    await h.user.click(screen.getByRole('button', { name: 'Enable Two-Factor Authentication' }))

    await waitFor(() => screen.getByAltText('Two-factor authentication QR code'))
    screen.getByPlaceholderText('123 456')
  })

  it('confirms the code and reveals recovery codes', async () => {
    h.mock(authService, 'setupTwoFactor').mockResolvedValue({
      provisioning_uri: 'otpauth://totp/Koel:foo@bar?secret=ABC&issuer=Koel',
    })
    h.mock(authService, 'confirmTwoFactor').mockResolvedValue({
      recovery_codes: ['AAAA BBBB', 'CCCC DDDD'],
    })
    renderDisabled()

    await h.user.click(screen.getByRole('button', { name: 'Enable Two-Factor Authentication' }))
    await waitFor(() => screen.getByPlaceholderText('123 456'))
    await h.type(screen.getByPlaceholderText('123 456'), '123456')
    await h.user.click(screen.getByRole('button', { name: 'Confirm' }))

    await waitFor(() => screen.getByText('AAAA BBBB'))
    screen.getByText('CCCC DDDD')
  })

  it('shows manage actions when enabled', () => {
    renderEnabled()

    screen.getByRole('button', { name: 'Regenerate Recovery Codes' })
    screen.getByRole('button', { name: 'Disable' })
  })

  it('regenerates recovery codes', async () => {
    const regenMock = h.mock(authService, 'regenerateRecoveryCodes').mockResolvedValue({
      recovery_codes: ['NEW1 NEW2', 'NEW3 NEW4'],
    })
    renderEnabled()

    await h.user.click(screen.getByRole('button', { name: 'Regenerate Recovery Codes' }))
    await h.type(screen.getByPlaceholderText('123 456'), '123456')
    await h.user.click(screen.getByRole('button', { name: 'Submit' }))

    expect(regenMock).toHaveBeenCalledWith('123456')
    await waitFor(() => screen.getByText('NEW1 NEW2'))
  })
})
