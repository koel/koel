import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor, within } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { DialogBoxStub } from '@/__tests__/stubs'
import { authService } from '@/services/authService'
import { userStore } from '@/stores/userStore'
import Component from './TwoFactorAuthSettings.vue'

describe('twoFactorAuthSettings.vue', () => {
  const h = createHarness()

  const renderEnabled = () =>
    h.actingAsUser(h.factory('user').make({ two_factor: true }) as CurrentUser).render(Component)

  const renderDisabled = () =>
    h.actingAsUser(h.factory('user').make({ two_factor: false }) as CurrentUser).render(Component)

  const typeTotp = async (digits: string) => {
    const boxes = within(screen.getByTestId('one-time-code-input')).getAllByRole<HTMLInputElement>('textbox')

    for (let i = 0; i < digits.length; i++) {
      await h.type(boxes[i], digits[i])
    }
  }

  it('shows the enable button when two-factor is off', () => {
    renderDisabled()
    screen.getByRole('button', { name: 'Enable Two-Factor Authentication' })
  })

  it('shows manage actions when enabled', () => {
    renderEnabled()

    screen.getByRole('button', { name: 'Regenerate Recovery Codes' })
    screen.getByRole('button', { name: 'Disable' })
  })

  it('flips userStore.two_factor true and reveals codes after a successful enrollment', async () => {
    h.mock(authService, 'enrollTwoFactor').mockResolvedValue({
      provisioning_uri: 'otpauth://totp/Koel:foo@bar?secret=ABC&issuer=Koel',
    })
    h.mock(authService, 'confirmTwoFactor').mockResolvedValue({
      recovery_codes: ['AAAA BBBB', 'CCCC DDDD'],
    })

    renderDisabled()

    await h.user.click(screen.getByRole('button', { name: 'Enable Two-Factor Authentication' }))
    await waitFor(() => screen.getByAltText('Two-factor authentication QR code'))

    await typeTotp('123456')

    await waitFor(() => screen.getByText('AAAA BBBB'))
    expect(userStore.state.current.two_factor).toBe(true)
  })

  it('flips userStore.two_factor false after a successful disable', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(true)
    h.mock(authService, 'disableTwoFactor').mockResolvedValue(undefined)

    renderEnabled()

    await h.user.click(screen.getByRole('button', { name: 'Disable' }))
    await typeTotp('654321')

    await waitFor(() => expect(userStore.state.current.two_factor).toBe(false))
  })
})
