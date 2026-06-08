import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './TwoFactorManageActions.vue'

describe('twoFactorManageActions.vue', () => {
  const h = createHarness()

  it('shows regenerate + disable buttons by default', () => {
    h.render(Component)

    screen.getByRole('button', { name: 'Regenerate Recovery Codes' })
    screen.getByRole('button', { name: 'Disable' })
    expect(screen.queryByTestId('two-factor-manage-form')).toBeNull()
  })

  it('emits regenerate with the typed code', async () => {
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Regenerate Recovery Codes' }))
    await h.type(screen.getByPlaceholderText('123 456'), '123456')
    await h.user.click(screen.getByRole('button', { name: 'Submit' }))

    expect(emitted().regenerate).toEqual([['123456']])
  })

  it('emits disable with the typed code', async () => {
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Disable' }))
    await h.type(screen.getByPlaceholderText('123 456'), '654321')
    await h.user.click(screen.getByRole('button', { name: 'Submit' }))

    expect(emitted().disable).toEqual([['654321']])
  })

  it('returns to the default state when cancelling the inline form', async () => {
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Disable' }))
    await h.user.click(screen.getByRole('button', { name: 'Cancel' }))

    screen.getByRole('button', { name: 'Regenerate Recovery Codes' })
    expect(screen.queryByTestId('two-factor-manage-form')).toBeNull()
  })
})
