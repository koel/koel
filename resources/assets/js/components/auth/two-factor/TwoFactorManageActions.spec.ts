import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor, within } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { DialogBoxStub } from '@/__tests__/stubs'
import { authService } from '@/services/authService'
import Component from './TwoFactorManageActions.vue'

describe('twoFactorManageActions.vue', () => {
  const h = createHarness()

  const typeTotp = async (digits: string) => {
    const boxes = within(screen.getByTestId('one-time-code-input')).getAllByRole<HTMLInputElement>('textbox')

    for (let i = 0; i < digits.length; i++) {
      await h.type(boxes[i], digits[i])
    }
  }

  it('shows regenerate + disable buttons by default', () => {
    h.render(Component)

    screen.getByRole('button', { name: 'Regenerate Recovery Codes' })
    screen.getByRole('button', { name: 'Disable' })
    expect(screen.queryByTestId('two-factor-manage-form')).toBeNull()
  })

  it('regenerates recovery codes and emits regenerated', async () => {
    const regenMock = h.mock(authService, 'regenerateRecoveryCodes').mockResolvedValue({
      recovery_codes: ['NEW1 NEW2', 'NEW3 NEW4'],
    })
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Regenerate Recovery Codes' }))
    await typeTotp('123456')

    expect(regenMock).toHaveBeenCalledWith('123456')
    await waitFor(() => expect(emitted().regenerated).toEqual([[['NEW1 NEW2', 'NEW3 NEW4']]]))
  })

  it('disables 2FA after confirm and emits disabled', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(true)
    const disableMock = h.mock(authService, 'disableTwoFactor').mockResolvedValue(undefined)
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Disable' }))
    await typeTotp('654321')

    expect(disableMock).toHaveBeenCalledWith('654321')
    await waitFor(() => expect(emitted().disabled).toBeTruthy())
  })

  it('skips the disable API call when the user dismisses the confirm dialog', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(false)
    const disableMock = h.mock(authService, 'disableTwoFactor')
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Disable' }))
    await typeTotp('654321')

    expect(disableMock).not.toHaveBeenCalled()
    expect(emitted().disabled).toBeFalsy()
  })

  it('returns to the default state when cancelling the inline form', async () => {
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Disable' }))
    await h.user.click(screen.getByRole('button', { name: 'Cancel' }))

    screen.getByRole('button', { name: 'Regenerate Recovery Codes' })
    expect(screen.queryByTestId('two-factor-manage-form')).toBeNull()
  })
})
