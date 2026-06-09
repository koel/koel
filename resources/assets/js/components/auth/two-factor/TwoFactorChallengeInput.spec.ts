import { describe, expect, it } from 'vite-plus/test'
import { screen, within } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './TwoFactorChallengeInput.vue'

describe('twoFactorChallengeInput.vue', () => {
  const h = createHarness({ authenticated: false })

  const getBoxes = () => within(screen.getByTestId('one-time-code-input')).getAllByRole<HTMLInputElement>('textbox')

  const lastUpdate = (emitted: Record<string, unknown[]>) => {
    const updates = (emitted['update:modelValue'] as string[][]) ?? []

    return updates[updates.length - 1]?.[0] ?? ''
  }

  it('renders the 6-box TOTP widget by default', () => {
    h.render(Component)

    screen.getByTestId('one-time-code-input')
    expect(screen.queryByTestId('recovery-code-input')).toBeNull()
  })

  it('emits complete when 6 digits are entered in TOTP mode', async () => {
    const { emitted } = h.render(Component)
    const boxes = getBoxes()

    for (let i = 0; i < 6; i++) {
      await h.type(boxes[i], String(i + 1))
    }

    expect(emitted().complete).toEqual([['123456']])
  })

  it('switches to recovery mode and renders the recovery input', async () => {
    h.render(Component)

    await h.user.click(screen.getByTestId('use-recovery-code'))

    expect(screen.queryByTestId('one-time-code-input')).toBeNull()
    screen.getByTestId('recovery-code-input')
  })

  it('normalises the recovery code on input (strips non-alphanumeric, uppercases, regroups)', async () => {
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByTestId('use-recovery-code'))
    const field = screen.getByTestId<HTMLInputElement>('recovery-code-input')

    await h.type(field, 'nsnyryjclhwt4ab54befizzfltysadnp')

    expect(lastUpdate(emitted())).toBe('NSNY RYJC LHWT 4AB5 4BEF IZZF LTYS ADNP')
  })

  it('toggles back to TOTP mode and clears the code', async () => {
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByTestId('use-recovery-code'))
    await h.type(screen.getByTestId<HTMLInputElement>('recovery-code-input'), 'AAAA')
    await h.user.click(screen.getByTestId('use-totp-code'))

    screen.getByTestId('one-time-code-input')
    expect(screen.queryByTestId('recovery-code-input')).toBeNull()
    expect(lastUpdate(emitted())).toBe('')
  })
})
